<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $orders = \App\Models\ClientOrder::whereNotIn('status', ['cancelled'])->get();
        $totalUsers = $orders->sum('users_count');
        
        // Active Subscriptions
        $activeSubscriptions = \App\Models\ClientOrder::whereNotIn('status', ['cancelled'])->count();
        $activeModules = \App\Models\Module::count();

        // Calculate Revenue from PAID Invoices
        $monthlyRevenue = \App\Models\Invoice::where('status', 'paid')
            ->where(function($q) {
                $q->whereYear('paid_at', date('Y'))
                  ->whereMonth('paid_at', date('m'))
                  ->orWhere(function($q2) {
                      $q2->whereNull('paid_at')
                         ->whereYear('created_at', date('Y'))
                         ->whereMonth('created_at', date('m'));
                  });
            })
            ->sum('amount');

        // Fallback to all-time paid revenue if monthly is 0
        $totalPaidRevenue = \App\Models\Invoice::where('status', 'paid')->sum('amount');
        $revenueVal = $monthlyRevenue > 0 ? $monthlyRevenue : $totalPaidRevenue;

        $formattedRevenue = 'Rp ' . number_format($revenueVal, 0, ',', '.');

        $stats = [
            'total_users' => $totalUsers,
            'total_users_growth' => '+0%',
            'active_subscriptions' => $activeSubscriptions,
            'active_subscriptions_growth' => '+0%',
            'revenue' => $formattedRevenue,
            'revenue_growth' => '+0%',
            'active_modules' => $activeModules,
            'active_modules_growth' => '+0',
        ];

        // Recent Activity from Orders & Invoices
        $latestOrders = \App\Models\ClientOrder::orderBy('created_at', 'desc')->take(5)->get();
        $recentActivity = $latestOrders->map(function($order) {
            $hasPaidInvoice = \App\Models\Invoice::where('client_order_id', $order->id)->where('status', 'paid')->exists();
            $statusLabel = $hasPaidInvoice ? 'lunas' : $order->status;
            return [
                'id' => $order->id,
                'user' => $order->company_name,
                'action' => 'Pesanan paket ' . $order->plan_name . ' (' . $statusLabel . ')',
                'time' => $order->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'user' => $request->user(),
        ]);
    }
}
