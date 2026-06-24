<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $pricingTiers = [
            [
                'id' => 'Starter',
                'name' => 'Starter',
                'subtitle' => 'Starter',
                'price' => 'Rp 0',
                'original_price' => null,
                'price_suffix' => '/bulan pertama',
                'description' => 'Gratis 1 bulan pertama, selanjutnya Rp 50.000/bulan. Fokus pada tampilan yang menarik dan order lebih cepat.',
                'features' => json_encode([
                    'Selanjutnya Rp 50.000 / bulan',
                    'Mobile-First Design (Responsif di HP)',
                    'Tombol WhatsApp Terintegrasi',
                    'Katalog Produk / Layanan Menarik',
                    'Profil Usaha Lengkap & Google Maps',
                    'Form Pemesanan Pemesanan Modern'
                ]),
                'popular' => false,
                'highlight_color' => 'from-blue-500/10 to-transparent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'Pro',
                'name' => 'Paid Pro',
                'subtitle' => 'Scale-Up',
                'price' => 'Rp 50.000',
                'original_price' => 'Rp 150.000',
                'price_suffix' => '/bulan',
                'description' => 'Harga promo untuk 3 bulan pertama, selanjutnya Rp 150.000/bulan.',
                'features' => json_encode([
                    'Selanjutnya Rp 150.000 / bulan',
                    'Opsi Bayar Tahunan: Diskon 25%',
                    'Seluruh Fitur Versi Starter',
                    'Aplikasi Manajemen Pendukung Bisnis',
                    'Kustom Domain Sendiri (.com/.id)',
                    'Dukungan & Support Prioritas',
                    'Pendampingan & Digital Marketing'
                ]),
                'popular' => true,
                'highlight_color' => 'from-[#ff8a65]/20 to-[#9f4122]/10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'Enterprise',
                'name' => 'Enterprise',
                'subtitle' => 'Korporat / Khusus',
                'price' => 'Kustom',
                'original_price' => null,
                'price_suffix' => '',
                'description' => 'Solusi terbaik untuk integrasi skala enterprise, kustom AI, dan keandalan penuh.',
                'features' => json_encode([
                    'Seluruh Keunggulan Pro & Starter',
                    'Kustomisasi & Automasi Penuh',
                    'Integrasi Model AI Bisnis Mandiri',
                    'Dedicated Environment',
                    'Dedicated Account Manager & SLA'
                ]),
                'popular' => false,
                'highlight_color' => 'from-purple-500/15 to-transparent',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        $modules = [
            [
                'id' => 'finance-ledger',
                'name' => 'Finance Ledger',
                'description' => 'Lacak pengeluaran, pemasukan, dan analisis profitabilitas otomatis dengan visualisasi chart interaktif.',
                'icon' => 'Wallet',
                'color' => 'bg-blue-500/10 text-blue-500 dark:text-blue-400 border-blue-500/20',
                'bg_grad' => 'bg-[#38BDF8]/10',
                'demo_type' => 'chart',
                'demo_title' => 'Streaming Dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'pospro-jastip',
                'name' => 'Pospro / Jastip Pro',
                'description' => 'Kelola pesanan jastip, lacak status kurir, hitung ongkos kirim otomatis, dan rekap tagihan pelanggan.',
                'icon' => 'Package',
                'color' => 'bg-orange-500/10 text-orange-500 dark:text-orange-400 border-orange-500/20',
                'bg_grad' => 'bg-[#FF5E3A]/10',
                'demo_type' => 'list',
                'demo_title' => 'Finance Ledger',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'travel-planner',
                'name' => 'Travel Planner',
                'description' => 'Buat rencana perjalanan, atur jadwal, estimasi biaya, dan optimalkan rute perjalanan bisnis Anda.',
                'icon' => 'MapPin',
                'color' => 'bg-rose-500/10 text-rose-500 dark:text-rose-400 border-rose-500/20',
                'bg_grad' => 'bg-[#FF9A9E]/10',
                'demo_type' => 'travel',
                'demo_title' => 'Travel Planner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'gym-pro',
                'name' => 'Gym Pro',
                'description' => 'Kelola keanggotaan gym, jadwalkan sesi latihan, pantau pembayaran bulanan, dan automasi notifikasi WhatsApp.',
                'icon' => 'Activity',
                'color' => 'bg-green-500/10 text-green-500 dark:text-green-400 border-green-500/20',
                'bg_grad' => 'bg-[#FFD166]/10',
                'demo_type' => 'grid',
                'demo_title' => 'Gym Pro Grid',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insert or update
        foreach ($pricingTiers as $tier) {
            DB::table('pricing_tiers')->updateOrInsert(['id' => $tier['id']], $tier);
        }

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(['id' => $module['id']], $module);
        }
    }
}
