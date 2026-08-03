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

        $secretKey = config('services.xendit.secret_key');
        if (empty($secretKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Xendit Secret Key is missing.'
            ], 500);
        }

        \Xendit\Configuration::setXenditKey($secretKey);
        
        $apiInstance = new \Xendit\Invoice\InvoiceApi();
        
        $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => 'LUV-' . $order->id . '-' . time(),
            'amount' => (int) $order->pricing_payment,
            'description' => 'Payment for Order ' . $order->company_name,
            'payer_email' => $order->email,
            'customer' => [
                'given_names' => $order->full_name,
                'email' => $order->email,
                'mobile_number' => $order->phone,
            ]
        ]);

        try {
            $result = $apiInstance->createInvoice($create_invoice_request);
            $paymentUrl = $result['invoice_url'];
            
            $order->update([
                'snap_token' => $result['id'],
                'payment_url' => $paymentUrl
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Link pembayaran berhasil dibuat.',
                'payment_url' => $paymentUrl,
                'snap_token' => $result['id']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
