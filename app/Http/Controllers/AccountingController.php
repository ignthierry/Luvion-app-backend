<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    // ==========================================
    // CHART OF ACCOUNTS (COA)
    // ==========================================
    public function getAccounts()
    {
        $accounts = Account::orderBy('code')->get();
        return response()->json($accounts);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:accounts,code',
            'name' => 'required|string',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account = Account::create($validated);
        return response()->json(['message' => 'Account created successfully', 'data' => $account], 201);
    }

    public function updateAccount(Request $request, $id)
    {
        $account = Account::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|string|unique:accounts,code,' . $account->id,
            'name' => 'required|string',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $account->update($validated);
        return response()->json(['message' => 'Account updated successfully', 'data' => $account]);
    }

    public function deleteAccount($id)
    {
        $account = Account::findOrFail($id);
        if ($account->journalDetails()->count() > 0) {
            return response()->json(['message' => 'Cannot delete account with existing transactions'], 400);
        }
        $account->delete();
        return response()->json(['message' => 'Account deleted successfully']);
    }

    // ==========================================
    // JOURNALS
    // ==========================================
    public function getJournals()
    {
        $journals = Journal::with('details.account')->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        return response()->json($journals);
    }

    public function storeJournal(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'required|string|unique:journals,reference',
            'description' => 'required|string',
            'details' => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.credit' => 'required|numeric|min:0',
            'details.*.description' => 'nullable|string',
        ]);

        $totalDebit = collect($validated['details'])->sum('debit');
        $totalCredit = collect($validated['details'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['message' => 'Debit and Credit must be balanced', 'debit' => $totalDebit, 'credit' => $totalCredit], 400);
        }

        try {
            DB::beginTransaction();

            $journal = Journal::create([
                'date' => $validated['date'],
                'reference' => $validated['reference'],
                'description' => $validated['description'],
                'total_amount' => $totalDebit,
            ]);

            foreach ($validated['details'] as $detail) {
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $detail['account_id'],
                    'debit' => $detail['debit'],
                    'credit' => $detail['credit'],
                    'description' => $detail['description'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Journal entry created successfully', 'data' => $journal->load('details.account')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create journal entry', 'error' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // REPORTS
    // ==========================================
    public function getBalanceSheet(Request $request)
    {
        // For MVP, simply aggregate by Account Type: Asset, Liability, Equity
        $endDate = $request->query('end_date', date('Y-m-d'));

        // Query balances (Debit - Credit for Assets, Credit - Debit for Liabilities/Equity)
        $accounts = Account::whereIn('type', ['Asset', 'Liability', 'Equity'])
            ->with(['journalDetails' => function ($q) use ($endDate) {
                $q->whereHas('journal', function ($q2) use ($endDate) {
                    $q2->where('date', '<=', $endDate);
                });
            }])
            ->get();

        $assets = [];
        $liabilities = [];
        $equity = [];

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $acc) {
            $debit = $acc->journalDetails->sum('debit');
            $credit = $acc->journalDetails->sum('credit');
            
            if ($acc->type === 'Asset') {
                $balance = $debit - $credit;
                if ($balance != 0) {
                    $assets[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalAssets += $balance;
                }
            } else if ($acc->type === 'Liability') {
                $balance = $credit - $debit;
                if ($balance != 0) {
                    $liabilities[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalLiabilities += $balance;
                }
            } else if ($acc->type === 'Equity') {
                $balance = $credit - $debit;
                if ($balance != 0) {
                    $equity[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalEquity += $balance;
                }
            }
        }

        // We also need to add Net Income to Equity for a proper balance sheet
        $netIncome = $this->calculateNetIncome($endDate);
        if ($netIncome != 0) {
            $equity[] = ['code' => '-', 'name' => 'Retained Earnings (Net Income)', 'balance' => $netIncome];
            $totalEquity += $netIncome;
        }

        return response()->json([
            'date' => $endDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
        ]);
    }

    public function getIncomeStatement(Request $request)
    {
        $startDate = $request->query('start_date', '1970-01-01');
        $endDate = $request->query('end_date', date('Y-m-d'));

        $accounts = Account::whereIn('type', ['Revenue', 'Expense'])
            ->with(['journalDetails' => function ($q) use ($startDate, $endDate) {
                $q->whereHas('journal', function ($q2) use ($startDate, $endDate) {
                    $q2->whereBetween('date', [$startDate, $endDate]);
                });
            }])
            ->get();

        $revenues = [];
        $expenses = [];

        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($accounts as $acc) {
            $debit = $acc->journalDetails->sum('debit');
            $credit = $acc->journalDetails->sum('credit');
            
            if ($acc->type === 'Revenue') {
                $balance = $credit - $debit; // Revenue is natural credit
                if ($balance != 0) {
                    $revenues[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalRevenue += $balance;
                }
            } else if ($acc->type === 'Expense') {
                $balance = $debit - $credit; // Expense is natural debit
                if ($balance != 0) {
                    $expenses[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance];
                    $totalExpense += $balance;
                }
            }
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense
        ]);
    }

    /**
     * Buku Besar (General Ledger): mutasi debit/kredit + saldo berjalan per akun.
     *
     * Query params:
     *   account_id  — filter satu akun (optional, default semua)
     *   start_date  — awal rentang (optional)
     *   end_date    — akhir rentang (optional, default hari ini)
     *   type        — filter tipe akun (Asset/Liability/Equity/Revenue/Expense)
     *
     * Saldo dihitung sesuai natural balance:
     *   Asset & Expense   → saldo = Debit - Kredit
     *   Liability, Equity & Revenue → saldo = Kredit - Debit
     */
    public function getLedger(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date', date('Y-m-d'));

        $accounts = Account::query()
            ->when($request->query('account_id'), function ($q, $id) {
                $q->where('id', $id);
            })
            ->when($request->query('type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $debitNatural = in_array($account->type, ['Asset', 'Expense']);

            // Semua detail jurnal akun ini (tanpa filter tanggal untuk saldo awal)
            $detailsQuery = $account->journalDetails()
                ->with('journal')
                ->orderBy('journal.date')
                ->orderBy('journal.id');

            $details = $detailsQuery->get();

            // Saldo awal = akumulasi sebelum start_date (jika ada)
            $openingBalance = 0;
            $mutations = [];

            foreach ($details as $detail) {
                $journalDate = $detail->journal ? $detail->journal->date : null;
                $d = (float) $detail->debit;
                $c = (float) $detail->credit;
                $delta = $debitNatural ? ($d - $c) : ($c - $d);

                if ($startDate && $journalDate && $journalDate < $startDate) {
                    $openingBalance += $delta;
                    continue;
                }

                if ($endDate && $journalDate && $journalDate > $endDate) {
                    continue;
                }

                $mutations[] = [
                    'id' => $detail->id,
                    'journal_id' => $detail->journal_id,
                    'journal_reference' => $detail->journal ? $detail->journal->reference : '-',
                    'journal_description' => $detail->journal ? $detail->journal->description : '-',
                    'date' => $journalDate,
                    'debit' => $d,
                    'credit' => $c,
                    'description' => $detail->description,
                ];
            }

            // Saldo berjalan per baris
            $runningBalance = $openingBalance;
            foreach ($mutations as &$m) {
                $delta = $debitNatural ? ($m['debit'] - $m['credit']) : ($m['credit'] - $m['debit']);
                $runningBalance += $delta;
                $m['balance'] = round($runningBalance, 2);
            }
            unset($m);

            $totalDebit = array_sum(array_column($mutations, 'debit'));
            $totalCredit = array_sum(array_column($mutations, 'credit'));
            $closingBalance = round($openingBalance + ($debitNatural ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit)), 2);

            $result[] = [
                'account' => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ],
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => $closingBalance,
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'mutations' => $mutations,
            ];
        }

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $result,
        ]);
    }

    private function calculateNetIncome($endDate)
    {
        $accounts = Account::whereIn('type', ['Revenue', 'Expense'])
            ->with(['journalDetails' => function ($q) use ($endDate) {
                $q->whereHas('journal', function ($q2) use ($endDate) {
                    $q2->where('date', '<=', $endDate);
                });
            }])
            ->get();

        $netIncome = 0;

        foreach ($accounts as $acc) {
            $debit = $acc->journalDetails->sum('debit');
            $credit = $acc->journalDetails->sum('credit');
            
            if ($acc->type === 'Revenue') {
                $netIncome += ($credit - $debit);
            } else if ($acc->type === 'Expense') {
                $netIncome -= ($debit - $credit);
            }
        }

        return $netIncome;
    }
}
