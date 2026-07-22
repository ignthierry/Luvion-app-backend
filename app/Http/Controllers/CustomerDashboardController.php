<?php

namespace App\Http\Controllers;

use App\Models\ClientOrder;
use App\Models\Invoice;
use App\Models\CustomerRequest;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Find orders linked to customer's email
        $orders = ClientOrder::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        $orderIds = $orders->pluck('id');

        // Find invoices linked to customer's orders (with clientOrder details)
        $invoices = Invoice::whereIn('client_order_id', $orderIds)
            ->with('clientOrder')
            ->orderBy('created_at', 'desc')
            ->get();

        // Find customer requests
        $requests = CustomerRequest::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        $latestOrder = $orders->first();

        $stats = [
            'active_plan' => $latestOrder ? $latestOrder->plan_name : 'Belum Ada',
            'subdomain' => $latestOrder && $latestOrder->subdomain ? 
                (str_contains($latestOrder->subdomain, '.') ? $latestOrder->subdomain : $latestOrder->subdomain . '.luvion.my.id') : '-',
            'total_orders' => $orders->count(),
            'total_invoices' => $invoices->count(),
            'unpaid_invoices' => $invoices->where('status', 'unpaid')->count(),
            'total_spent' => 'Rp ' . number_format($invoices->where('status', 'paid')->sum('amount'), 0, ',', '.'),
        ];

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'orders' => $orders,
            'invoices' => $invoices,
            'requests' => $requests,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function storeRequest(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'request_type' => 'required|string|in:addon,upgrade_plan,extra_licenses,custom_feature,bug_report',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_order_id' => 'nullable|integer',
        ]);

        $customerRequest = CustomerRequest::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'client_order_id' => $validated['client_order_id'] ?? null,
            'request_type' => $validated['request_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Request fitur/paket berhasil dikirim ke tim Luvion.',
            'data' => $customerRequest
        ], 201);
    }

    public function updateOrder(Request $request, $id)
    {
        $user = $request->user();

        // Ensure order belongs to logged-in customer's email
        $order = ClientOrder::where('id', $id)
            ->where('email', $user->email)
            ->firstOrFail();

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'subdomain' => 'nullable|string|max:255',
            'theme_color' => 'nullable|string|max:50',
            'purpose' => 'nullable|string',
            'integration_needs' => 'nullable|string',
            'notes' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = array_filter($request->only([
            'company_name', 'full_name', 'phone', 
            'subdomain', 'theme_color', 'purpose', 
            'integration_needs', 'notes'
        ]), function($val) {
            return $val !== null;
        });

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $order->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil proyek & logo perusahaan berhasil diperbarui.',
            'data' => $order
        ]);
    }
}
