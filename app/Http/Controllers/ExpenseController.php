<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * List expenses with their journal linkage.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['account', 'journal'])->orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Summary stats for the Pembiayaan dashboard header.
     */
    public function stats()
    {
        $totalCount = Expense::count();
        $totalAmount = Expense::sum('amount');
        $thisMonth = Expense::where('date', '>=', now()->startOfMonth())->sum('amount');
        $topCategory = Expense::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        return response()->json([
            'total_expenses' => $totalCount,
            'total_amount' => (float) $totalAmount,
            'this_month' => (float) $thisMonth,
            'top_category' => $topCategory ? $topCategory->category : null,
        ]);
    }

    /**
     * Register a new expense (pembiayaan).
     * Automatically posts the journal: Debit Biaya, Credit Kas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:50',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        try {
            DB::beginTransaction();

            $expense = Expense::create([
                'date' => $validated['date'],
                'reference' => $request->reference ?: Expense::generateReference(),
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
            ]);

            // Auto journal: Debit Biaya, Credit Kas (asas akuntansi).
            $journal = AccountingService::postExpenseJournal($expense);
            if ($journal) {
                $expense->journal_id = $journal->id;
                $expense->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembiayaan berhasil dicatat. Jurnal otomatis: Debit Biaya, Kredit Kas.',
                'data' => $expense->load(['account', 'journal.details.account']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan pembiayaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an expense and its linked journal.
     */
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        try {
            DB::beginTransaction();

            if ($expense->journal) {
                $expense->journal->delete(); // cascades journal_details
            }
            $expense->delete();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pembiayaan dihapus beserta jurnalnya']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}