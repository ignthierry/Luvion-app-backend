<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
    .sub { text-align: center; font-size: 11px; color: #444; margin-bottom: 14px; }
    .acct { background: #eef; font-weight: bold; padding: 4px 8px; margin-top: 12px; border-left: 3px solid #339; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #dde; border: 1px solid #999; padding: 4px 6px; text-align: left; font-size: 10px; }
    td { border: 1px solid #ccc; padding: 3px 6px; }
    .r { text-align: right; }
    .total { background: #f5f5f5; font-weight: bold; }
    .grand { background: #eee; font-weight: bold; border-top: 2px solid #333; }
    .muted { color: #777; }
</style>
</head>
<body>
@include('exports.partials.header')
<h1>BUKU BESAR (GENERAL LEDGER)</h1>
<div class="sub">Periode: {{ $start }} s/d {{ $end }} — Luvion Enterprise</div>

@foreach($accounts as $acc)
    <div class="acct">{{ $acc['account']['code'] }} — {{ $acc['account']['name'] }} ({{ $acc['account']['type'] }})</div>
    <table>
        <thead>
            <tr>
                <th style="width:10%">Tanggal</th>
                <th style="width:14%">Referensi</th>
                <th>Keterangan</th>
                <th style="width:12%" class="r">Debit</th>
                <th style="width:12%" class="r">Kredit</th>
                <th style="width:12%" class="r">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($acc['mutations'] as $m)
            <tr>
                <td>{{ $m['date'] }}</td>
                <td>{{ $m['journal_reference'] }}</td>
                <td>{{ $m['journal_description'] }}</td>
                <td class="r">{{ $m['debit'] > 0 ? number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                <td class="r">{{ $m['credit'] > 0 ? number_format($m['credit'], 0, ',', '.') : '-' }}</td>
                <td class="r">{{ number_format($m['balance'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="muted">Tidak ada mutasi pada periode ini.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="2">Saldo Awal: {{ number_format($acc['opening_balance'], 0, ',', '.') }}</td>
                <td>Total</td>
                <td class="r">{{ number_format($acc['total_debit'], 0, ',', '.') }}</td>
                <td class="r">{{ number_format($acc['total_credit'], 0, ',', '.') }}</td>
                <td class="r">{{ number_format($acc['closing_balance'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
@endforeach

</body>
</html>
