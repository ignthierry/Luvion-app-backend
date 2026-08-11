<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 16px; text-align: center; margin: 0 0 4px; }
    .sub { text-align: center; font-size: 11px; color: #444; margin-bottom: 14px; }
    h2 { font-size: 13px; border-bottom: 2px solid #333; padding-bottom: 3px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #dde; border: 1px solid #999; padding: 4px 6px; text-align: left; }
    td { border: 1px solid #ccc; padding: 3px 6px; }
    .r { text-align: right; }
    .grand { background: #eee; font-weight: bold; font-size: 13px; }
    .subtotal { background: #f5f5f5; font-weight: bold; }
</style>
</head>
<body>
@include('exports.partials.header')
<h1>LAPORAN LABA RUGI</h1>
<div class="sub">Periode: {{ $start }} s/d {{ $end }} — Luvion Enterprise</div>

<h2>PENDAPATAN</h2>
<table>
    <thead><tr><th style="width:12%">Kode</th><th>Akun</th><th style="width:18%" class="r">Jumlah</th></tr></thead>
    <tbody>
        @forelse($report['revenues'] as $r)
        <tr><td>{{ $r['code'] }}</td><td>{{ $r['name'] }}</td><td class="r">{{ number_format($r['balance'], 0, ',', '.') }}</td></tr>
        @empty
        <tr><td colspan="3" class="muted">Tidak ada pendapatan.</td></tr>
        @endforelse
        <tr class="subtotal"><td colspan="2">TOTAL PENDAPATAN</td><td class="r">{{ number_format($report['total_revenue'], 0, ',', '.') }}</td></tr>
    </tbody>
</table>

<h2>BEBAN</h2>
<table>
    <thead><tr><th style="width:12%">Kode</th><th>Akun</th><th style="width:18%" class="r">Jumlah</th></tr></thead>
    <tbody>
        @forelse($report['expenses'] as $e)
        <tr><td>{{ $e['code'] }}</td><td>{{ $e['name'] }}</td><td class="r">{{ number_format($e['balance'], 0, ',', '.') }}</td></tr>
        @empty
        <tr><td colspan="3" class="muted">Tidak ada beban.</td></tr>
        @endforelse
        <tr class="subtotal"><td colspan="2">TOTAL BEBAN</td><td class="r">{{ number_format($report['total_expense'], 0, ',', '.') }}</td></tr>
    </tbody>
</table>

<table style="margin-top:18px">
    <tr class="grand"><td>LABA BERSIH</td><td class="r">{{ number_format($report['net_income'], 0, ',', '.') }}</td></tr>
</table>

</body>
</html>
