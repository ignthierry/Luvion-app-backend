<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Export Buku Besar ke Excel (XLSX)
     */
    public function ledgerExcel(Request $request)
    {
        $accounts = $this->ledgerData($request);
        $data = [];

        // Header
        $data[] = ['BUKU BESAR (GENERAL LEDGER)'];
        $data[] = ['Periode: ' . ($request->query('start_date') ?: 'Awal') . ' s/d ' . $request->query('end_date', date('Y-m-d'))];
        $data[] = [];

        foreach ($accounts as $acc) {
            $data[] = [$acc['account']['code'] . ' - ' . $acc['account']['name'] . ' (' . $acc['account']['type'] . ')'];
            $data[] = ['Tanggal', 'Referensi', 'Keterangan', 'Debit', 'Kredit', 'Saldo'];
            foreach ($acc['mutations'] as $m) {
                $data[] = [
                    $m['date'],
                    $m['journal_reference'],
                    $m['journal_description'],
                    $m['debit'] > 0 ? number_format($m['debit'], 2) : '',
                    $m['credit'] > 0 ? number_format($m['credit'], 2) : '',
                    number_format($m['balance'], 2),
                ];
            }
            $data[] = ['', '', 'Total', number_format($acc['total_debit'], 2), number_format($acc['total_credit'], 2), number_format($acc['closing_balance'], 2)];
            $data[] = [];
            $data[] = [];
        }

        return $this->excelResponse($data, 'buku-besar.xlsx');
    }

    /**
     * Export Buku Besar ke PDF
     */
    public function ledgerPdf(Request $request)
    {
        $accounts = $this->ledgerData($request);
        $start = $request->query('start_date') ?: 'Awal';
        $end = $request->query('end_date', date('Y-m-d'));

        $pdf = Pdf::loadView('exports.ledger', [
            'accounts' => $accounts,
            'start' => $start,
            'end' => $end,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('buku-besar-' . date('Ymd') . '.pdf');
    }

    /**
     * Export Laba Rugi ke Excel
     */
    public function incomeExcel(Request $request)
    {
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));
        $report = $this->incomeData($startDate, $endDate);

        $data = [];
        $data[] = ['LAPORAN LABA RUGI (INCOME STATEMENT)'];
        $data[] = ['Periode: ' . $startDate . ' s/d ' . $endDate];
        $data[] = [];

        $data[] = ['PENDAPATAN'];
        $data[] = ['Kode', 'Akun', 'Jumlah'];
        foreach ($report['revenues'] as $r) {
            $data[] = [$r['code'], $r['name'], number_format($r['balance'], 2)];
        }
        $data[] = ['', 'Total Pendapatan', number_format($report['total_revenue'], 2)];
        $data[] = [];

        $data[] = ['BEBAN'];
        $data[] = ['Kode', 'Akun', 'Jumlah'];
        foreach ($report['expenses'] as $e) {
            $data[] = [$e['code'], $e['name'], number_format($e['balance'], 2)];
        }
        $data[] = ['', 'Total Beban', number_format($report['total_expense'], 2)];
        $data[] = [];

        $data[] = ['', 'LABA BERSIH', number_format($report['net_income'], 2)];

        return $this->excelResponse($data, 'laba-rugi.xlsx');
    }

    /**
     * Export Laba Rugi ke PDF
     */
    public function incomePdf(Request $request)
    {
        $startDate = $request->query('start_date', date('Y-m-01'));
        $endDate = $request->query('end_date', date('Y-m-d'));
        $report = $this->incomeData($startDate, $endDate);

        $pdf = Pdf::loadView('exports.income', [
            'report' => $report,
            'start' => $startDate,
            'end' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laba-rugi-' . date('Ymd') . '.pdf');
    }

    /**
     * Export Neraca ke Excel
     */
    public function balanceExcel(Request $request)
    {
        $endDate = $request->query('end_date', date('Y-m-d'));
        $report = $this->balanceData($endDate);

        $data = [];
        $data[] = ['NERACA (BALANCE SHEET)'];
        $data[] = ['Per Tanggal: ' . $endDate];
        $data[] = [];

        $data[] = ['ASET', '', 'KEWAJIBAN', ''];
        $data[] = ['Kode', 'Akun', 'Kode', 'Akun'];
        $maxRows = max(count($report['assets']), count($report['liabilities']), count($report['equity']));
        for ($i = 0; $i < $maxRows; $i++) {
            $a = $report['assets'][$i] ?? null;
            $l = $report['liabilities'][$i] ?? null;
            $e = $report['equity'][$i] ?? null;
            $data[] = [
                $a ? $a['code'] : '',
                $a ? $a['name'] . ' - ' . number_format($a['balance'], 2) : '',
                $l ? $l['code'] : ($e ? $e['code'] : ''),
                $l ? $l['name'] . ' - ' . number_format($l['balance'], 2) : ($e ? $e['name'] . ' - ' . number_format($e['balance'], 2) : ''),
            ];
        }
        $data[] = ['', 'Total Aset: ' . number_format($report['total_assets'], 2), '', 'Total Kewajiban+Ekuitas: ' . number_format($report['total_liabilities'] + $report['total_equity'], 2)];
        $data[] = ['', '', '', 'Seimbang: ' . ($report['is_balanced'] ? 'YA' : 'TIDAK')];

        return $this->excelResponse($data, 'neraca.xlsx');
    }

    /**
     * Export Neraca ke PDF
     */
    public function balancePdf(Request $request)
    {
        $endDate = $request->query('end_date', date('Y-m-d'));
        $report = $this->balanceData($endDate);

        $pdf = Pdf::loadView('exports.balance', [
            'report' => $report,
            'end' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('neraca-' . date('Ymd') . '.pdf');
    }

    /**
     * Export SPT Tahunan Badan (1771) ke PDF
     */
    public function sptPdf(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $report = $this->incomeData($startDate, $endDate);
        $balance = $this->balanceData($endDate);

        $netIncome = max($report['net_income'], 0);
        // Tarif PPh Badan 22% (UU HPP). WP dengan peredaran ≤ 50M dapat fasilitas 50% atas PKP s.d. 4,8M (Pasal 31E)
        $pkpUpTo4800 = min($netIncome, 4800000000);
        $pph = ($pkpUpTo4800 * 0.5 * 0.22) + (max($netIncome - $pkpUpTo4800, 0) * 0.22);

        $pdf = Pdf::loadView('exports.spt', [
            'report' => $report,
            'balance' => $balance,
            'year' => $year,
            'pph' => round($pph),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('SPT-1771-' . $year . '.pdf');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function excelResponse(array $rows, string $filename)
    {
        $fp = fopen('php://temp', 'w');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function ledgerData(Request $request): array
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
            $details = $account->journalDetails()
                ->with('journal')
                ->get()
                ->sortBy(fn($detail) => $detail->journal ? $detail->journal->date : '0000-00-00')
                ->values();

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
                if ($endDate && $journalDate && $journalDate > $endDate) continue;

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

            $runningBalance = $openingBalance;
            foreach ($mutations as &$m) {
                $delta = $debitNatural ? ($m['debit'] - $m['credit']) : ($m['credit'] - $m['debit']);
                $runningBalance += $delta;
                $m['balance'] = round($runningBalance, 2);
            }
            unset($m);

            $totalDebit = array_sum(array_column($mutations, 'debit'));
            $totalCredit = array_sum(array_column($mutations, 'credit'));
            $result[] = [
                'account' => ['id' => $account->id, 'code' => $account->code, 'name' => $account->name, 'type' => $account->type],
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => round($openingBalance + ($debitNatural ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit)), 2),
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'mutations' => $mutations,
            ];
        }
        return $result;
    }

    private function incomeData(string $startDate, string $endDate): array
    {
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
                $balance = $credit - $debit;
                if ($balance != 0) { $revenues[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance]; $totalRevenue += $balance; }
            } else if ($acc->type === 'Expense') {
                $balance = $debit - $credit;
                if ($balance != 0) { $expenses[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance]; $totalExpense += $balance; }
            }
        }

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
        ];
    }

    private function balanceData(string $endDate): array
    {
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
                if ($balance != 0) { $assets[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance]; $totalAssets += $balance; }
            } else if ($acc->type === 'Liability') {
                $balance = $credit - $debit;
                if ($balance != 0) { $liabilities[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance]; $totalLiabilities += $balance; }
            } else if ($acc->type === 'Equity') {
                $balance = $credit - $debit;
                if ($balance != 0) { $equity[] = ['code' => $acc->code, 'name' => $acc->name, 'balance' => $balance]; $totalEquity += $balance; }
            }
        }

        $netIncome = 0;
        foreach (Account::whereIn('type', ['Revenue', 'Expense'])->with(['journalDetails' => function ($q) use ($endDate) {
            $q->whereHas('journal', function ($q2) use ($endDate) { $q2->where('date', '<=', $endDate); });
        }])->get() as $acc) {
            $debit = $acc->journalDetails->sum('debit');
            $credit = $acc->journalDetails->sum('credit');
            if ($acc->type === 'Revenue') $netIncome += ($credit - $debit);
            else if ($acc->type === 'Expense') $netIncome -= ($debit - $credit);
        }
        if ($netIncome != 0) {
            $equity[] = ['code' => '-', 'name' => 'Retained Earnings (Net Income)', 'balance' => $netIncome];
            $totalEquity += $netIncome;
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }
}
