<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $orders = \App\Models\ClientOrder::whereNotIn('status', ['cancelled'])->get();
        $totalUsers = $orders->sum('users_count');
        $activeSubscriptions = $orders->count();
        $activeModules = \App\Models\Module::count();

        // Calculate Revenue
        $pricingTiers = \App\Models\PricingTier::all()->keyBy('name');
        $revenue = 0;
        foreach ($orders as $order) {
            $tier = $pricingTiers->get($order->plan_name);
            if ($tier) {
                // Remove non-numeric characters for sum
                $priceVal = (int) preg_replace('/[^0-9]/', '', $tier->price);
                $revenue += $priceVal;
            }
        }

        $formattedRevenue = 'Rp ' . number_format($revenue, 0, ',', '.');

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

        // Recent Activity from Orders
        $latestOrders = \App\Models\ClientOrder::orderBy('created_at', 'desc')->take(5)->get();
        $recentActivity = $latestOrders->map(function($order) {
            return [
                'id' => $order->id,
                'user' => $order->company_name,
                'action' => 'Pesanan paket ' . $order->plan_name . ' (' . $order->status . ')',
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
