<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Module;

$accurixDoc = <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accurix — Enterprise Financial & Accounting System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #090d16;
      --card-bg: rgba(18, 26, 44, 0.75);
      --card-border: rgba(56, 189, 248, 0.15);
      --primary: #38bdf8;
      --primary-glow: rgba(56, 189, 248, 0.25);
      --accent: #818cf8;
      --emerald: #34d399;
      --amber: #fbbf24;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text-main);
      line-height: 1.75;
      padding: 32px 20px 60px;
      background-image: 
        radial-gradient(circle at 5% 15%, rgba(56, 189, 248, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 95% 85%, rgba(129, 140, 248, 0.08) 0%, transparent 40%);
      background-attachment: fixed;
    }
    .wrap { max-width: 880px; margin: 0 auto; }
    
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: rgba(56, 189, 248, 0.12);
      border: 1px solid rgba(56, 189, 248, 0.3);
      color: var(--primary);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-radius: 999px;
      margin-bottom: 16px;
    }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 8px var(--primary); }

    h1 {
      font-size: 2.3rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.25;
      margin-bottom: 12px;
      background: linear-gradient(135deg, #38bdf8 0%, #818cf8 50%, #c084fc 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .subtitle { color: var(--text-muted); font-size: 1.05rem; font-weight: 500; margin-bottom: 32px; }

    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin: 24px 0; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 24px 0; }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 22px;
      backdrop-filter: blur(16px);
      transition: all 0.3s ease;
    }
    .card:hover {
      border-color: rgba(56, 189, 248, 0.4);
      transform: translateY(-2px);
      box-shadow: 0 12px 30px -10px rgba(0,0,0,0.5), 0 0 15px var(--primary-glow);
    }
    .card-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid rgba(56, 189, 248, 0.3);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }
    .card h4 { font-size: 1.05rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
    .card p { font-size: 0.88rem; color: #cbd5e1; line-height: 1.6; }

    .section-title {
      font-size: 1.35rem;
      font-weight: 700;
      color: #f8fafc;
      margin: 40px 0 18px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .pipeline-step {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
      background: rgba(18, 26, 44, 0.5);
      border: 1px solid rgba(255,255,255,0.06);
      padding: 16px;
      border-radius: 14px;
    }
    .step-num {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: linear-gradient(135deg, #0284c7, #38bdf8);
      color: #fff;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      shrink: 0;
    }

    table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 12px; overflow: hidden; }
    th, td { padding: 12px 16px; text-align: left; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
    th { background: rgba(56, 189, 248, 0.1); color: var(--primary); font-weight: 700; }
    td { background: rgba(15, 23, 42, 0.4); color: #cbd5e1; }
    tr:hover td { background: rgba(56, 189, 248, 0.05); }

    .stat-pill { display: inline-block; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; }
    .stat-emerald { background: rgba(52, 211, 153, 0.15); color: var(--emerald); border: 1px solid rgba(52, 211, 153, 0.3); }
    .stat-blue { background: rgba(56, 189, 248, 0.15); color: var(--primary); border: 1px solid rgba(56, 189, 248, 0.3); }

    .highlight-box {
      background: linear-gradient(135deg, rgba(56, 189, 248, 0.1) 0%, rgba(129, 140, 248, 0.1) 100%);
      border: 1px solid rgba(56, 189, 248, 0.3);
      padding: 20px;
      border-radius: 16px;
      margin: 28px 0;
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="badge"><span class="badge-dot"></span> Enterprise Financial Architecture</div>
  <h1>Luvion Accurix</h1>
  <p class="subtitle">Platform Sistem Akuntansi & Manajemen Keuangan Presisi Tinggi untuk Otomasi Pembukuan Bisnis.</p>

  <div class="highlight-box">
    <h3 style="color: #38bdf8; font-size: 1.1rem; margin-bottom: 6px;">💡 Ikhtisar Solusi</h3>
    <p style="font-size: 0.92rem; color: #e2e8f0;">
      Accurix dirancang untuk memecahkan kendala klasik pembukuan manual: rekonsiliasi yang lambat, human-error pencatatan debit/kredit, dan laporan keuangan yang terlambat. Dengan sistem <strong>Double-Entry Accounting terotomasi</strong> dan validasi jurnal instan, data keuangan perusahaan selalu sinkron, akurat, dan siap diaudit kapan saja.
    </p>
  </div>

  <h2 class="section-title">✨ Modul & Fitur Unggulan</h2>
  
  <div class="grid-2">
    <div class="card">
      <div class="card-icon">📊</div>
      <h4>General Ledger & Jurnal Otomatis</h4>
      <p>Pencatatan otomatis setiap transaksi penjualan, pembelian, dan beban operasional ke buku besar dengan kaidah debit-kredit terstandar SAK.</p>
    </div>

    <div class="card">
      <div class="card-icon">⚡</div>
      <h4>Rekonsiliasi Bank AI-Assisted</h4>
      <p>Pencocokan mutasi bank dengan riwayat invoice secara otomatis hingga tingkat akurasi 99.8%, mempercepat penutupan buku bulanan (closing).</p>
    </div>

    <div class="card">
      <div class="card-icon">📑</div>
      <h4>Laporan Finansial Komprehensif</h4>
      <p>Generate instan laporan Laba Rugi (P&L), Neraca Saldo (Balance Sheet), Arus Kas (Cash Flow), dan Buku Pembantu Piutang/Hutang dalam 1 klik.</p>
    </div>

    <div class="card">
      <div class="card-icon">💳</div>
      <h4>Invoicing & Multi-Gateway Billing</h4>
      <p>Penerbitan faktur digital dengan QRIS, Virtual Account, kalkulasi PPN/PPh otomatis, serta pengiriman tagihan otomatis via WhatsApp & Email.</p>
    </div>
  </div>

  <h2 class="section-title">🔄 Alur Kerja Sistem Finansial (Workflow)</h2>
  
  <div class="pipeline-step">
    <div class="step-num">1</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Transaksi Terjadi (Sales / Purchase / Expense)</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Input kasir, invoice client, atau pencatatan pengeluaran harian langsung tercatat ke sistem.</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">2</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Otomasi Pembuatan Jurnal Finansial</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Algoritma Accurix memetakan akun COA (Chart of Accounts) debit dan kredit secara seimbang (*balanced entry*).</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">3</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Sinkronisasi Rekening & Audit Trail</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Sistem memverifikasi mutasi bank, menyimpan log aktivitas pengguna, dan mencegah perubahan data yang tidak terotorisasi.</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">4</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Eksekutif Dashboard & Tax Ready Report</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Tampilan KPI keuangan real-time untuk pengambil keputusan dan export data laporan pajak resmi.</p>
    </div>
  </div>

  <h2 class="section-title">📈 Nilai Tambah & Efisiensi Bisnis</h2>
  <table>
    <thead>
      <tr>
        <th>Aspek Operasional</th>
        <th>Sebelum Accurix</th>
        <th>Dengan Luvion Accurix</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Waktu Rekonsiliasi</strong></td>
        <td>3 - 5 Hari Kerja per bulan</td>
        <td><span class="stat-pill stat-emerald">Real-time / Hitungan Detik</span></td>
      </tr>
      <tr>
        <td><strong>Human Error Pencatatan</strong></td>
        <td>Rentan selisih hitung jurnal</td>
        <td><span class="stat-pill stat-emerald">0% (Sistem Double-Entry Terkunci)</span></td>
      </tr>
      <tr>
        <td><strong>Visibilitas Arus Kas</strong></td>
        <td>Menunggu rekap akhir bulan</td>
        <td><span class="stat-pill stat-blue">Live Telemetri 24/7</span></td>
      </tr>
      <tr>
        <td><strong>Keamanan & Log Audit</strong></td>
        <td>Sulit melacak pengubah file Excel</td>
        <td><span class="stat-pill stat-blue">Enkripsi Token & Log IP Lengkap</span></td>
      </tr>
    </tbody>
  </table>

  <h2 class="section-title">🛡️ Ringkasan Arsitektur Teknis</h2>
  <div class="grid-3">
    <div class="card">
      <h4>Backend API</h4>
      <p>Laravel 11 REST API, Sanctum Auth, Transaction Locking.</p>
    </div>
    <div class="card">
      <h4>Frontend Dashboard</h4>
      <p>Next.js 16, React 19, Tailwind CSS, Framer Motion.</p>
    </div>
    <div class="card">
      <h4>Integritas Data</h4>
      <p>MySQL Enterprise Engine, Automated Daily DB Snapshots.</p>
    </div>
  </div>

</div>
</body>
</html>
HTML;

$shoeProDoc = <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ShoePro by Luvion — Shoe Care & Laundry Management System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0b0f17;
      --card-bg: rgba(22, 28, 45, 0.75);
      --card-border: rgba(249, 115, 22, 0.18);
      --primary: #f97316;
      --primary-glow: rgba(249, 115, 22, 0.25);
      --accent: #fbbf24;
      --emerald: #34d399;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text-main);
      line-height: 1.75;
      padding: 32px 20px 60px;
      background-image: 
        radial-gradient(circle at 5% 15%, rgba(249, 115, 22, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 95% 85%, rgba(251, 191, 36, 0.08) 0%, transparent 40%);
      background-attachment: fixed;
    }
    .wrap { max-width: 880px; margin: 0 auto; }
    
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: rgba(249, 115, 22, 0.12);
      border: 1px solid rgba(249, 115, 22, 0.3);
      color: var(--primary);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-radius: 999px;
      margin-bottom: 16px;
    }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 8px var(--primary); }

    h1 {
      font-size: 2.3rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.25;
      margin-bottom: 12px;
      background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fcd34d 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .subtitle { color: var(--text-muted); font-size: 1.05rem; font-weight: 500; margin-bottom: 32px; }

    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin: 24px 0; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 24px 0; }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 22px;
      backdrop-filter: blur(16px);
      transition: all 0.3s ease;
    }
    .card:hover {
      border-color: rgba(249, 115, 22, 0.4);
      transform: translateY(-2px);
      box-shadow: 0 12px 30px -10px rgba(0,0,0,0.5), 0 0 15px var(--primary-glow);
    }
    .card-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: rgba(249, 115, 22, 0.15);
      border: 1px solid rgba(249, 115, 22, 0.3);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }
    .card h4 { font-size: 1.05rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
    .card p { font-size: 0.88rem; color: #cbd5e1; line-height: 1.6; }

    .section-title {
      font-size: 1.35rem;
      font-weight: 700;
      color: #f8fafc;
      margin: 40px 0 18px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .pipeline-step {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
      background: rgba(22, 28, 45, 0.5);
      border: 1px solid rgba(255,255,255,0.06);
      padding: 16px;
      border-radius: 14px;
    }
    .step-num {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: linear-gradient(135deg, #ea580c, #f97316);
      color: #fff;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      shrink: 0;
    }

    table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 12px; overflow: hidden; }
    th, td { padding: 12px 16px; text-align: left; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
    th { background: rgba(249, 115, 22, 0.1); color: var(--primary); font-weight: 700; }
    td { background: rgba(15, 23, 42, 0.4); color: #cbd5e1; }
    tr:hover td { background: rgba(249, 115, 22, 0.05); }

    .highlight-box {
      background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
      border: 1px solid rgba(249, 115, 22, 0.3);
      padding: 20px;
      border-radius: 16px;
      margin: 28px 0;
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="badge"><span class="badge-dot"></span> Specialized Shoe Care POS & Operations</div>
  <h1>ShoePro by Luvion</h1>
  <p class="subtitle">Platform Kasir POS, Manajemen Antrean Cuci, dan Otomasi Notifikasi WhatsApp untuk Bisnis Laundry Sepatu.</p>

  <div class="highlight-box">
    <h3 style="color: #f97316; font-size: 1.1rem; margin-bottom: 6px;">👟 Mengapa Bisnis Shoe Care Membutuhkan ShoePro?</h3>
    <p style="font-size: 0.92rem; color: #e2e8f0;">
      Bisnis cuci sepatu memiliki kompleksitas unik: pelacakan status per pasang sepatu, variasi treatment (*Deep Clean, Unyellowing, Reglue, Repaint*), foto kondisi awal (*kondisi sebelum dicuci*), hingga keluhan pelanggan karena kurangnya transparansi pengerjaan. ShoePro menyatukan seluruh operasional dari kasir hingga teknisi dalam 1 sistem terintegrasi.
    </p>
  </div>

  <h2 class="section-title">✨ Fitur Kunci ShoePro</h2>
  
  <div class="grid-2">
    <div class="card">
      <div class="card-icon">🏷️</div>
      <h4>POS Kasir & Smart QR Tagging</h4>
      <p>Cetak label barcode/QR tahan air per pasang sepatu saat pelanggan drop-off, mencatat brand, warna, ukuran, dan jenis treatment.</p>
    </div>

    <div class="card">
      <div class="card-icon">📱</div>
      <h4>Otomasi WhatsApp Status</h4>
      <p>Kirim notifikasi otomatis ke WhatsApp pelanggan saat status berubah: *"Sepatu Anda Diterima"*, *"Sedang Dicuci"*, hingga *"Siap Diambil"* tanpa kirim manual.</p>
    </div>

    <div class="card">
      <div class="card-icon">🧪</div>
      <h4>Manajemen Bahan & Stok Treatment</h4>
      <p>Pantau sisa sabun cleaner, cat unyellowing, lem sepatu, dan parfum laundry dengan sistem peringatan stok menipis otomatis.</p>
    </div>

    <div class="card">
      <div class="card-icon">👥</div>
      <h4>Kalkulasi Komisi Teknisi / Washer</h4>
      <p>Hitung insentif pengerjaan cuci per pasang untuk staf teknisi secara adil dan transparan berdasarkan log pengerjaan sistem.</p>
    </div>
  </div>

  <h2 class="section-title">🔄 Alur Operasional (Service Workflow)</h2>
  
  <div class="pipeline-step">
    <div class="step-num">1</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Drop-off, QC Awal & Foto Kondisi</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Kasir input data pelanggan, foto kondisi sebelum pengerjaan, dan cetak QR tag fisik.</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">2</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Pengerjaan oleh Teknisi (Treatment Queue)</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Teknisi scan QR sepatu untuk memulai sesi cuci, repaint, unyellowing, atau reglue.</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">3</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Quality Control (QC) & Foto Hasil Selesai</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Pemeriksaan hasil akhir sebelum packing. Sistem memicu WhatsApp otomatis ke nomor pelanggan.</p>
    </div>
  </div>

  <div class="pipeline-step">
    <div class="step-num">4</div>
    <div>
      <h4 style="color: #f8fafc; font-size: 0.95rem;">Pelunasan & Pengambilan (Pickup)</h4>
      <p style="font-size: 0.85rem; color: #94a3b8;">Scan barcode saat serah terima barang, pembayaran via QRIS/Tunai, dan nota lunas digital.</p>
    </div>
  </div>

  <h2 class="section-title">📊 Spesifikasi Menu & Laporan</h2>
  <table>
    <thead>
      <tr>
        <th>Fitur</th>
        <th>Kapabilitas</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Dukungan Perangkat</strong></td>
        <td>Tablet Android/iPad, Laptop Kasir, Thermal Printer Bluetooth/USB</td>
      </tr>
      <tr>
        <td><strong>Katalog Layanan</strong></td>
        <td>Deep Clean, Fast Clean, Repaint, Reglue, Unyellowing, Leather Treatment</td>
      </tr>
      <tr>
        <td><strong>Laporan Keuangan</strong></td>
        <td>Omset harian kasir, performa cabang (*Multi-Outlet*), rekap metode bayar</td>
      </tr>
      <tr>
        <td><strong>Database Pelanggan (CRM)</strong></td>
        <td>Riwayat cuci pelanggan, program poin loyalti, promo broadcast otomatis</td>
      </tr>
    </tbody>
  </table>

</div>
</body>
</html>
HTML;

$storefrontDoc = <<<'HTML'
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luvion Storefront — Next-Gen Multi-Niche E-Commerce SaaS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #090e17;
      --card-bg: rgba(20, 29, 48, 0.75);
      --card-border: rgba(245, 158, 11, 0.2);
      --primary: #f59e0b;
      --primary-glow: rgba(245, 158, 11, 0.25);
      --accent: #38bdf8;
      --emerald: #34d399;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text-main);
      line-height: 1.75;
      padding: 32px 20px 60px;
      background-image: 
        radial-gradient(circle at 5% 15%, rgba(245, 158, 11, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 95% 85%, rgba(56, 189, 248, 0.08) 0%, transparent 40%);
      background-attachment: fixed;
    }
    .wrap { max-width: 880px; margin: 0 auto; }
    
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.3);
      color: var(--primary);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-radius: 999px;
      margin-bottom: 16px;
    }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 8px var(--primary); }

    h1 {
      font-size: 2.3rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.25;
      margin-bottom: 12px;
      background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #38bdf8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .subtitle { color: var(--text-muted); font-size: 1.05rem; font-weight: 500; margin-bottom: 32px; }

    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin: 24px 0; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 24px 0; }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 22px;
      backdrop-filter: blur(16px);
      transition: all 0.3s ease;
    }
    .card:hover {
      border-color: rgba(245, 158, 11, 0.4);
      transform: translateY(-2px);
      box-shadow: 0 12px 30px -10px rgba(0,0,0,0.5), 0 0 15px var(--primary-glow);
    }
    .card-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.3);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }
    .card h4 { font-size: 1.05rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
    .card p { font-size: 0.88rem; color: #cbd5e1; line-height: 1.6; }

    .section-title {
      font-size: 1.35rem;
      font-weight: 700;
      color: #f8fafc;
      margin: 40px 0 18px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .highlight-box {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(56, 189, 248, 0.1) 100%);
      border: 1px solid rgba(245, 158, 11, 0.3);
      padding: 20px;
      border-radius: 16px;
      margin: 28px 0;
    }

    table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 12px; overflow: hidden; }
    th, td { padding: 12px 16px; text-align: left; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
    th { background: rgba(245, 158, 11, 0.1); color: var(--primary); font-weight: 700; }
    td { background: rgba(15, 23, 42, 0.4); color: #cbd5e1; }
  </style>
</head>
<body>
<div class="wrap">

  <div class="badge"><span class="badge-dot"></span> Omnichannel SaaS E-Commerce</div>
  <h1>Luvion Storefront</h1>
  <p class="subtitle">Platform Toko Online Multi-Niche Siap Pakai dengan Sinkronisasi Kasir POS, Web Store, dan WhatsApp Checkout.</p>

  <div class="highlight-box">
    <h3 style="color: #f59e0b; font-size: 1.1rem; margin-bottom: 6px;">🛍️ Mengapa Memilih Luvion Storefront?</h3>
    <p style="font-size: 0.92rem; color: #e2e8f0;">
      Luvion Storefront dibangun di atas arsitektur *Headless Commerce modern* yang menjamin kecepatan loading super kilat, SEO teroptimasi, dan kebebasan kustomisasi untuk berbagai jenis industri: Ritel & Fashion, Kuliner & Bakery, Elektronik & Gadget, Mainan & Hobi, hingga Produk Digital & Kursus Online.
    </p>
  </div>

  <h2 class="section-title">✨ Fitur Unggulan E-Commerce</h2>
  
  <div class="grid-2">
    <div class="card">
      <div class="card-icon">⚡</div>
      <h4>Toko Online Ultra-Fast & Mobile First</h4>
      <p>Desain UI kelas atas yang responsif, katalog produk varian dinamis, filter pintar, dan pengalaman belanja instan tanpa lag.</p>
    </div>

    <div class="card">
      <div class="card-icon">🔄</div>
      <h4>Omnichannel POS & Web Store Sync</h4>
      <p>Stok produk berkurang serentak saat terjadi pembelian di kasir toko offline maupun checkout di website.</p>
    </div>

    <div class="card">
      <div class="card-icon">🚚</div>
      <h4>Cek Ongkir Otomatis 15+ Ekspedisi</h4>
      <p>Terhubung langsung dengan API kurir (JNE, SiCepat, J&T, AnterAja, GoSend, GrabExpress) dengan cetak resi otomatis.</p>
    </div>

    <div class="card">
      <div class="card-icon">💳</div>
      <h4>Payment Gateway Otomatis</h4>
      <p>Mendukung pembayaran instan: QRIS All Payment, BCA/Mandiri/BRI Virtual Account, GoPay, OVO, ShopeePay, dan Kartu Kredit.</p>
    </div>
  </div>

  <h2 class="section-title">🎯 Solusi Sesuai Niche Bisnis</h2>
  <table>
    <thead>
      <tr>
        <th>Niche Bisnis</th>
        <th>Contoh Implementasi & Keunggulan Khusus</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Ritel & Fashion</strong></td>
        <td>Varian ukuran/warna, panduan size chart, live stock alert, flash sale timer</td>
      </tr>
      <tr>
        <td><strong>Kuliner & F&B</strong></td>
        <td>Pilihan kurir instan, pengingat jam buka-tutup toko, request catatan menu</td>
      </tr>
      <tr>
        <td><strong>Elektronik & Gadget</strong></td>
        <td>Pencatatan nomor seri (IMEI/Serial), opsi asuransi pengiriman, garansi resmi</td>
      </tr>
      <tr>
        <td><strong>Mainan & Hobi (Toys)</strong></td>
        <td>Sistem Pre-Order (PO), DP/Down Payment booking, pelacakan rilis produk</td>
      </tr>
    </tbody>
  </table>

</div>
</body>
</html>
HTML;

// Update Accurix
$acc = Module::find('Accurix');
if ($acc) {
    $acc->documentation = $accurixDoc;
    $acc->save();
    echo "Accurix documentation updated.\n";
}

// Update ShoePro
$shoe = Module::find('booms');
if ($shoe) {
    $shoe->documentation = $shoeProDoc;
    $shoe->save();
    echo "ShoePro documentation updated.\n";
}

// Update Storefront
$store = Module::find('e-commerce');
if ($store) {
    $store->documentation = $storefrontDoc;
    $store->save();
    echo "Storefront documentation updated.\n";
}

echo "All module documentations updated successfully.\n";
