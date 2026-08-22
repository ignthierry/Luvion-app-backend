<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Invoice;
use App\Models\ClientOrder;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

/**
 * AccountingService — central helper for automatic journal entries.
 *
 * Ensures every business transaction produces a balanced double-entry
 * journal (debit = credit) under a single DB transaction, idempotent
 * against duplicate webhooks (Xendit can retry callbacks).
 */
class AccountingService
{
    /**
     * Post the revenue journal for a paid invoice.
     *
     * Asas akuntansi:
     *   Debit  Rekening Bank PT (1002)  [or Piutang Klien when previously invoiced]
     *   Credit Pendapatan Jasa (4001/4002)
     */
    public static function postRevenueFromInvoice(Invoice $invoice): ?Journal
    {
        if (!$invoice->clientOrder || !$invoice->amount || $invoice->amount <= 0) {
            return null;
        }

        $ref = 'REV-' . strtoupper($invoice->invoice_number);
        if (Journal::where('reference', $ref)->exists()) {
            return Journal::where('reference', $ref)->first(); // idempotent
        }

        $order = $invoice->clientOrder;

        // Revenue account: use maintenance/hosting income for recurring plans,
        // otherwise software development services.
        $revenueAccount = Account::where('code', '4002')->first()
            ?? Account::where('type', 'Revenue')->first();
        if (!$revenueAccount) {
            return null;
        }

        // Cash account: bank if the invoice was paid via Xendit, else cash.
        $cashAccount = Account::where('code', '1002')->first()
            ?? Account::where('type', 'Asset')->first();
        if (!$cashAccount) {
            return null;
        }

        // Receivable account (piutang) for not-yet-paid invoices.
        $receivableAccount = Account::where('code', '1003')->first();

        try {
            DB::beginTransaction();

            $journal = Journal::create([
                'date' => $invoice->paid_at ?? now(),
                'reference' => $ref,
                'description' => 'Pendapatan dari ' . ($order->company_name ?: $order->full_name . ' (' . ($order->plan_name ?? 'Order #' . $order->id) . ')'),
                'total_amount' => $invoice->amount,
            ]);

            // Debit: bank/cash (or receivable when the invoice is still unpaid)
            $debitAccount = $receivableAccount && $invoice->status !== 'paid'
                ? $receivableAccount
                : $cashAccount;

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'debit' => $invoice->amount,
                'credit' => 0,
                'description' => 'Penerimaan dari ' . ($order->company_name ?: $order->full_name),
            ]);

            // Credit: revenue
            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $revenueAccount->id,
                'debit' => 0,
                'credit' => $invoice->amount,
                'description' => 'Pendapatan ' . ($order->plan_name ?? 'jasa'),
            ]);

            DB::commit();
            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return null;
        }
    }

    /**
     * Post the expense journal for a recorded biaya.
     *
     * Asas akuntansi:
     *   Debit  Biaya (5001..5004 / selected expense account)
     *   Credit Kas Tunai (1001)  [default] or other asset/cash account
     */
    public static function postExpenseJournal(Expense $expense): ?Journal
    {
        $ref = 'EXP-' . $expense->reference;
        if (Journal::where('reference', $ref)->exists()) {
            return Journal::where('reference', $ref)->first();
        }

        // Expense account (Debit): default by category code (5001..5015) or first Expense account
        $expenseAccount = Account::where('code', static::codeForCategory($expense->category))->first()
            ?? ($expense->account && $expense->account->type === 'Expense' ? $expense->account : null)
            ?? Account::where('type', 'Expense')->first();
        if (!$expenseAccount) {
            return null;
        }

        // Cash / Bank account (Credit): user selected account_id (Kas Tunai, Rekening Bank BCA, Merchant BCA, etc.)
        $cashAccount = ($expense->account && $expense->account->type === 'Asset' ? $expense->account : null)
            ?? Account::where('code', '1001')->first()
            ?? Account::where('type', 'Asset')->first();
        if (!$cashAccount) {
            return null;
        }

        try {
            DB::beginTransaction();

            $journal = Journal::create([
                'date' => $expense->date,
                'reference' => $ref,
                'description' => $expense->category . ($expense->description ? ' — ' . $expense->description : ''),
                'total_amount' => $expense->amount,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'debit' => $expense->amount,
                'credit' => 0,
                'description' => $expense->category,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $expense->amount,
                'description' => 'Pembayaran ' . $expense->category . ' (' . $cashAccount->name . ')',
            ]);

            DB::commit();
            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return null;
        }
    }

    /**
     * Map an expense category to the default COA code.
     */
    public static function codeForCategory(string $category): string
    {
        $map = [
            'server' => '5001',
            'software' => '5002',
            'utilities' => '5003',
            'marketing' => '5004',
            'gaji' => '5005',
            'transport' => '5006',
            'operasional' => '5007',
            'sewa' => '5008',
            'bahan_baku' => '5009',
            'lisensi' => '5010',
            'konsumsi' => '5011',
            'pelatihan' => '5012',
            'komunikasi' => '5013',
            'perawatan' => '5014',
            'lainnya' => '5015',
        ];

        return $map[$category] ?? '5015';
    }
}