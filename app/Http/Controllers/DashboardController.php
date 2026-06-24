<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Mocking dynamic data for the dashboard
        $stats = [
            'total_users' => 1250,
            'total_users_growth' => '+12.5%',
            'active_subscriptions' => 840,
            'active_subscriptions_growth' => '+5.2%',
            'revenue' => 'Rp 45.200.000',
            'revenue_growth' => '+18.1%',
            'active_modules' => 15,
            'active_modules_growth' => '+2',
        ];

        $recentActivity = [
            [
                'id' => 1,
                'user' => 'Budi Santoso',
                'action' => 'Berlangganan modul CRM AI',
                'time' => '10 menit yang lalu'
            ],
            [
                'id' => 2,
                'user' => 'Siti Aminah',
                'action' => 'Mengubah paket langganan ke Enterprise',
                'time' => '1 jam yang lalu'
            ],
            [
                'id' => 3,
                'user' => 'Ahmad Reza',
                'action' => 'Mendaftar akun baru',
                'time' => '3 jam yang lalu'
            ],
            [
                'id' => 4,
                'user' => 'PT Maju Mundur',
                'action' => 'Pembayaran tagihan bulan ini berhasil',
                'time' => '5 jam yang lalu'
            ],
            [
                'id' => 5,
                'user' => 'CV Makmur Jaya',
                'action' => 'Menambahkan modul Automasi Marketing',
                'time' => '1 hari yang lalu'
            ],
        ];

        return response()->json([
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'user' => $request->user(),
        ]);
    }
}
