<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 15px; text-align: center; margin: 2px 0 2px; }
    h2 { font-size: 12px; border-bottom: 2px solid #333; padding-bottom: 3px; margin-top: 14px; }
    .sub { text-align: center; font-size: 10px; color: #444; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th { background: #dde; border: 1px solid #999; padding: 3px 5px; text-align: left; }
    td { border: 1px solid #ccc; padding: 3px 5px; }
    .r { text-align: right; }
    .c { text-align: center; }
    .grand { background: #eee; font-weight: bold; }
    .lbl { background: #f7f7f7; font-weight: bold; }
    .muted { color: #777; }
    .box { border: 1px solid #999; padding: 6px; margin-top: 8px; }
    .sign { margin-top: 30px; }
    .sign td { border: none; }
</style>
</head>
<body>
@include('exports.partials.header')
<h1>FORMULIR SPT TAHUNAN PAJAK PENGHASILAN WAJIB PAJAK BADAN</h1>
<h1>TAHUN PAJAK {{ $year }}</h1>
<div class="sub">1771 - Lampiran I (Neraca & Laba Rugi) & Perhitungan PPh Terutang</div>

<table>
    <tr><td style="width:35%" class="lbl">Nama Wajib Pajak</td><td>{{ config('company.name') }}</td></tr>
    <tr><td class="lbl">NPWP</td><td>{{ config('company.npwp') }}</td></tr>
    <tr><td class="lbl">Alamat</td><td>{{ config('company.address') }}</td></tr>
    <tr><td class="lbl">Tahun Pajak</td><td>{{ $year }}</td></tr>
    <tr><td class="lbl">Kode KLU</td><td>{{ config('company.klu') }}</td></tr>
</table>

<h2>A. LAPORAN LABA RUGI (Per {{ $year }})</h2>
<table>
    <thead><tr><th style="width:60%">Uraian</th><th class="r">Jumlah (Rp)</th></tr></thead>
    <tbody>
        <tr><td>Total Pendapatan Usaha</td><td class="r">{{ number_format($report['total_revenue'], 0, ',', '.') }}</td></tr>
        <tr><td>Total Beban / Biaya Usaha</td><td class="r">({{ number_format($report['total_expense'], 0, ',', '.') }})</td></tr>
        <tr class="grand"><td>LABA (RUGI) SEBELUM PAJAK</td><td class="r">{{ number_format($report['net_income'], 0, ',', '.') }}</td></tr>
    </tbody>
</table>

<h2>B. NERACA (Per 31 Desember {{ $year }})</h2>
<table>
    <tr>
        <td style="width:50%; vertical-align:top; border:none; padding-right:8px;">
            <table>
                <thead><tr><th>ASET</th><th class="r">Jumlah</th></tr></thead>
                <tbody>
                    @forelse($balance['assets'] as $a)
                    <tr><td>{{ $a['name'] }}</td><td class="r">{{ number_format($a['balance'], 0, ',', '.') }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="muted">Tidak ada aset</td></tr>
                    @endforelse
                    <tr class="grand"><td>TOTAL ASET</td><td class="r">{{ number_format($balance['total_assets'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; border:none; padding-left:8px;">
            <table>
                <thead><tr><th>KEWAJIBAN & EKUITAS</th><th class="r">Jumlah</th></tr></thead>
                <tbody>
                    @foreach($balance['liabilities'] as $l)
                    <tr><td>{{ $l['name'] }}</td><td class="r">{{ number_format($l['balance'], 0, ',', '.') }}</td></tr>
                    @endforeach
                    @foreach($balance['equity'] as $e)
                    <tr><td>{{ $e['name'] }}</td><td class="r">{{ number_format($e['balance'], 0, ',', '.') }}</td></tr>
                    @endforeach
                    <tr class="grand"><td>TOTAL KEWAJIBAN & EKUITAS</td><td class="r">{{ number_format($balance['total_liabilities'] + $balance['total_equity'], 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<h2>C. PERHITUNGAN PPh TERUTANG</h2>
<div class="box">
@if($taxMode === 'final_umkm')
<table>
    <tr><td style="width:60%">Rezim Pajak</td><td class="c"><b>PPh FINAL UMKM 0,5%</b> (PP 55/2022)</td></tr>
    <tr><td>Peredaran Bruto (Omzet) Tahun {{ $year }}</td><td class="r">{{ number_format($grossRevenue, 0, ',', '.') }}</td></tr>
    @if($subjectType === 'orang_pribadi')
    <tr><td>Omzet Bebas Pajak (Rp500 juta pertama, khusus WOP)</td><td class="r">(500.000.000)</td></tr>
    <tr><td>Omzet Kena Pajak (0,5%)</td><td class="r">{{ number_format(max($grossRevenue - 500000000, 0), 0, ',', '.') }}</td></tr>
    @endif
    <tr class="grand"><td>PPh Final Terutang (0,5% × Omzet Kena Pajak)</td><td class="r">{{ number_format($pph, 0, ',', '.') }}</td></tr>
    <tr><td colspan="2" class="muted">
        @if($subjectType === 'orang_pribadi')
        * Omzet s.d. Rp500 juta pertama per tahun <b>bebas pajak</b> (tidak dikenakan PPh 0,5%) — khusus Wajib Pajak Orang Pribadi.
        @else
        * Berlaku utk Wajib Pajak Orang Pribadi & PT Perorangan dengan peredaran bruto di bawah Rp4,8 miliar/tahun.
        @endif
    </td></tr>
</table>
@else
<table>
    <tr><td style="width:60%">Penghasilan Kena Pajak (PKP) = Laba Sebelum Pajak</td><td class="r">{{ number_format($netIncome, 0, ',', '.') }}</td></tr>
    <tr><td>Tarif PPh Badan (UU HPP, pasal 17/31E)</td><td class="c">22%</td></tr>
    <tr class="grand"><td>PPh Terutang (22% × PKP)</td><td class="r">{{ number_format($pph, 0, ',', '.') }}</td></tr>
    <tr><td class="muted" colspan="2">* Untuk WP dengan peredaran bruto ≤ Rp50 M, fasilitas pengurangan 50% berlaku atas bagian PKP sampai Rp4,8 M (Pasal 31E UU PPh)</td></tr>
</table>
@endif
</div>

<table class="sign">
    <tr>
        <td style="width:33%"></td>
        <td style="width:34%" class="c">
            Sidoarjo, {{ date('d F Y') }}<br/>
            Direktur,
            <br/><br/><br/><br/>
            <u>{{ config('company.director') }}</u><br/>
            Direktur Utama
        </td>
        <td style="width:33%"></td>
    </tr>
</table>

<div style="margin-top:12px; font-size:8px; color:#666; text-align:center;">
    Dokumen ini dihasilkan otomatis dari sistem Luvion Accounting. Sertakan Buku Besar & Jurnal sebagai lampiran pendukung.
</div>
</body>
</html>
