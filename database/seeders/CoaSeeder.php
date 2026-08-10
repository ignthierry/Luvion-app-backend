<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Aset
            ['code' => '1001', 'name' => 'Kas Tunai', 'type' => 'Asset', 'description' => 'Uang kas di tangan'],
            ['code' => '1002', 'name' => 'Rekening Bank PT', 'type' => 'Asset', 'description' => 'Saldo di rekening bank perusahaan'],
            ['code' => '1003', 'name' => 'Piutang Klien', 'type' => 'Asset', 'description' => 'Tagihan yang belum dibayar oleh klien'],
            ['code' => '1004', 'name' => 'Aset Tetap (Komputer/Server)', 'type' => 'Asset', 'description' => 'Nilai aset perangkat keras perusahaan'],
            
            // Kewajiban
            ['code' => '2001', 'name' => 'Hutang Usaha', 'type' => 'Liability', 'description' => 'Hutang ke vendor atau pihak ketiga'],
            ['code' => '2002', 'name' => 'Hutang Pajak', 'type' => 'Liability', 'description' => 'Pajak yang harus disetorkan ke kas negara'],
            
            // Ekuitas
            ['code' => '3001', 'name' => 'Modal Disetor', 'type' => 'Equity', 'description' => 'Modal awal/tambahan dari pemilik atau investor'],
            
            // Pendapatan
            ['code' => '4001', 'name' => 'Pendapatan Jasa Pembuatan Aplikasi', 'type' => 'Revenue', 'description' => 'Pendapatan dari proyek pengembangan software'],
            ['code' => '4002', 'name' => 'Pendapatan Maintenance/Hosting', 'type' => 'Revenue', 'description' => 'Pendapatan dari biaya langganan maintenance'],
            
            // Beban
            ['code' => '5001', 'name' => 'Biaya Server/Cloud', 'type' => 'Expense', 'description' => 'Biaya AWS, Vercel, Supabase, dll'],
            ['code' => '5002', 'name' => 'Langganan Software', 'type' => 'Expense', 'description' => 'Biaya langganan GitHub, ChatGPT, dll'],
            ['code' => '5003', 'name' => 'Biaya Internet & Listrik', 'type' => 'Expense', 'description' => 'Biaya utilitas kantor/operasional'],
            ['code' => '5004', 'name' => 'Biaya Pemasaran', 'type' => 'Expense', 'description' => 'Biaya iklan atau marketing'],
        ];

        foreach ($accounts as $account) {
            \App\Models\Account::updateOrCreate(
                ['code' => $account['code']], 
                $account
            );
        }
    }
}
