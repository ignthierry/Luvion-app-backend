<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PricingTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingTiers = [
            [
                'id' => 'Starter',
                'name' => 'Starter',
                'subtitle' => 'Entry Level',
                'price' => 'Rp 79.000',
                'original_price' => 'Rp 99.000',
                'price_suffix' => '/ bulan',
                'description' => 'Untuk perorangan, pemilik toko kecil, atau bisnis yang baru mulai go-digital.',
                'features' => [
                    'Akses ke fitur utama (Basic)',
                    '1 User (Owner)',
                    'Maksimal 100 transaksi / bulan',
                    'Kapasitas penyimpanan standar',
                    'Self-service support (Panduan & Grup)'
                ],
                'popular' => false,
                'highlight_color' => 'from-blue-500/10 to-transparent'
            ],
            [
                'id' => 'Pro',
                'name' => 'Paid Pro',
                'subtitle' => 'Scale-Up',
                'price' => 'Rp 199.000',
                'original_price' => 'Rp 299.000',
                'price_suffix' => '/ bulan',
                'description' => 'Pilihan tepat untuk UKM dengan tim kecil yang mengandalkan efisiensi fitur otomatis.',
                'features' => [
                    'Semua fitur di paket Starter',
                    '3 - 5 kuota akun staff',
                    'Transaksi Harian/Bulanan Unlimited',
                    'Fitur Otomatisasi (Notifikasi, Laporan, dll)',
                    'Dukungan Prioritas via WhatsApp / Chat'
                ],
                'popular' => true,
                'highlight_color' => 'from-[#ff8a65]/20 to-[#9f4122]/10'
            ],
            [
                'id' => 'Enterprise',
                'name' => 'Enterprise',
                'subtitle' => 'B2B / Agensi',
                'price' => 'Kustom',
                'original_price' => null,
                'price_suffix' => '',
                'description' => 'Untuk bisnis skala besar, butuh penyesuaian alur khusus atau integrasi penuh sistem internal.',
                'features' => [
                    'Pengguna / Staff Unlimited',
                    'Kustomisasi Domain Sendiri (Whitelabel)',
                    'Akses API untuk Integrasi Pihak Ketiga',
                    'Dedicated Server (Performa Diisolasi)',
                    'Prioritas Dukungan Penuh (SLA Support)'
                ],
                'popular' => false,
                'highlight_color' => 'from-purple-500/15 to-transparent'
            ]
        ];

        foreach ($pricingTiers as $tier) {
            \App\Models\PricingTier::updateOrCreate(
                ['id' => $tier['id']],
                $tier
            );
        }
    }
}
