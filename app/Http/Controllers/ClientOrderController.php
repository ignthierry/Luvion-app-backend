<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ClientOrder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientOrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'website' => 'nullable|string|max:255',
            'plan_name' => 'required|string',
            'billing_cycle' => 'required|string',
            'users_count' => 'required|integer|min:1',
            'purpose' => 'nullable|string',
            'addons' => 'nullable|array',
            'integration_needs' => 'nullable|string',
            'subdomain' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'theme_color' => 'nullable|string',
            'notes' => 'nullable|string',
            'timeline' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $order = ClientOrder::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    public function index()
    {
        $orders = ClientOrder::orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function show($id)
    {
        $order = ClientOrder::findOrFail($id);
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = ClientOrder::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'plan_name' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'users_count' => 'nullable|integer',
            'pricing_payment' => 'nullable|numeric',
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,paid,overdue,failed',
            'billing_due_day' => 'nullable|integer|min:1|max:31',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($request->only([
            'full_name', 'company_name', 'email', 'phone', 
            'plan_name', 'billing_cycle', 'users_count', 'pricing_payment', 
            'status', 'payment_status', 'billing_due_day'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order
        ]);
    }

    public function destroy($id)
    {
        $order = ClientOrder::findOrFail($id);
        
        if ($order->logo_path) {
            Storage::disk('public')->delete($order->logo_path);
        }
        
        $order->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully'
        ]);
    }

    public function generatePaymentLink($id)
    {
        $order = ClientOrder::findOrFail($id);

        if (!$order->pricing_payment || $order->pricing_payment <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak valid karena pricing_payment (harga) belum diset.'
            ], 422);
        }

        $serverKey = env('MIDTRANS_SERVER_KEY');
        if (empty($serverKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Midtrans Server Key tidak terbaca. Harap RESTART terminal "php artisan serve" Anda agar sistem membaca file .env yang baru.'
            ], 500);
        }

        // Set Midtrans configuration
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = array(
            'transaction_details' => array(
                'order_id' => 'LUV-' . $order->id . '-' . time(),
                'gross_amount' => (int) $order->pricing_payment,
            ),
            'customer_details' => array(
                'first_name' => $order->full_name,
                'email' => $order->email,
                'phone' => $order->phone,
            ),
        );

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $paymentUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
            
            $order->update([
                'snap_token' => $snapToken,
                'payment_url' => $paymentUrl
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Link pembayaran berhasil dibuat.',
                'payment_url' => $paymentUrl,
                'snap_token' => $snapToken
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
