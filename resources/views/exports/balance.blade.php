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
    .muted { color: #777; }
</style>
</head>
<body>
@include('exports.partials.header')
<h1>NERACA</h1>
<div class="sub">Per {{ $end }} — Luvion Enterprise</div>

<table>
    <tr>
        <td style="width:50%; vertical-align:top; border:none; padding-right:10px;">
            <h2>ASET</h2>
            <table>
                <thead><tr><th style="width:12%">Kode</th><th>Akun</th><th style="width:20%" class="r">Jumlah</th></tr></thead>
                <tbody>
                    @forelse($report['assets'] as $a)
                    <tr><td>{{ $a['code'] }}</td><td>{{ $a['name'] }}</td><td class="r">{{ number_format($a['balance'], 0, ',', '.') }}</td></tr>
                    @empty
                    <tr><td colspan="3" class="muted">Tidak ada aset.</td></tr>
                    @endforelse
                    <tr class="subtotal"><td colspan="2">TOTAL ASET</td><td class="r">{{ number_format($report['total_assets'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; border:none; padding-left:10px;">
            <h2>KEWAJIBAN</h2>
            <table>
                <thead><tr><th style="width:12%">Kode</th><th>Akun</th><th style="width:20%" class="r">Jumlah</th></tr></thead>
                <tbody>
                    @forelse($report['liabilities'] as $l)
                    <tr><td>{{ $l['code'] }}</td><td>{{ $l['name'] }}</td><td class="r">{{ number_format($l['balance'], 0, ',', '.') }}</td></tr>
                    @empty
                    <tr><td colspan="3" class="muted">Tidak ada kewajiban.</td></tr>
                    @endforelse
                    <tr class="subtotal"><td colspan="2">TOTAL KEWAJIBAN</td><td class="r">{{ number_format($report['total_liabilities'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>

            <h2>EKUITAS</h2>
            <table>
                <thead><tr><th style="width:12%">Kode</th><th>Akun</th><th style="width:20%" class="r">Jumlah</th></tr></thead>
                <tbody>
                    @forelse($report['equity'] as $e)
                    <tr><td>{{ $e['code'] }}</td><td>{{ $e['name'] }}</td><td class="r">{{ number_format($e['balance'], 0, ',', '.') }}</td></tr>
                    @empty
                    <tr><td colspan="3" class="muted">Tidak ada ekuitas.</td></tr>
                    @endforelse
                    <tr class="subtotal"><td colspan="2">TOTAL EKUITAS</td><td class="r">{{ number_format($report['total_equity'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table style="margin-top:16px">
    <tr class="grand"><td>TOTAL KEWAJIBAN + EKUITAS</td><td class="r">{{ number_format($report['total_liabilities'] + $report['total_equity'], 0, ',', '.') }}</td></tr>
    <tr class="grand"><td>SEIMBANG (BALANCED)</td><td class="r">{{ $report['is_balanced'] ? 'YA ✓' : 'TIDAK ✗' }}</td></tr>
</table>

</body>
</html>
