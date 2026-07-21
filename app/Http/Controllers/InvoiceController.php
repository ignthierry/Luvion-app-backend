<?php

namespace App\Http\Controllers;

use App\Models\ClientOrder;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function indexByOrder($orderId)
    {
        $invoices = Invoice::where('client_order_id', $orderId)->orderBy('created_at', 'desc')->get();
        return response()->json($invoices);
    }

    public function storeForOrder(Request $request, $orderId)
    {
        $order = ClientOrder::findOrFail($orderId);

        if (!$order->pricing_payment || $order->pricing_payment <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan ini belum memiliki harga dasar (pricing_payment). Harap atur harga langganan di detail pesanan terlebih dahulu.'
            ], 422);
        }

        // Generate Invoice Number (INV-YYYYMM-ORDERID-X)
        $yearMonth = date('Ym');
        $count = Invoice::where('client_order_id', $orderId)->count() + 1;
        $invoiceNumber = 'INV-' . $yearMonth . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'client_order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $order->pricing_payment,
            'status' => 'unpaid',
            // Default due date to billing_due_day of the current month if exists, else +7 days
            'due_date' => $order->billing_due_day ? date('Y-m-') . str_pad($order->billing_due_day, 2, '0', STR_PAD_LEFT) : date('Y-m-d', strtotime('+7 days'))
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice berhasil dibuat.',
            'data' => $invoice
        ]);
    }

    public function show($id)
    {
        $invoice = Invoice::with('clientOrder')->findOrFail($id);
        return response()->json($invoice);
    }

    public function generatePaymentLink($id)
    {
        $invoice = Invoice::with('clientOrder')->findOrFail($id);
        $order = $invoice->clientOrder;

        if ($invoice->amount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak valid karena nominal belum diset.'
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
                'order_id' => $invoice->invoice_number . '-' . time(),
                'gross_amount' => (int) $invoice->amount,
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
            
            $invoice->update([
                'snap_token' => $snapToken,
                'payment_url' => $paymentUrl
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Link pembayaran Midtrans berhasil dibuat.',
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

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKeyIn = $payload['signature_key'] ?? '';
        $serverKey = env('MIDTRANS_SERVER_KEY', '');
        
        // Verifikasi Signature Key dari Midtrans
        $calculatedSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        if ($calculatedSignature !== $signatureKeyIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature'
            ], 403);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        
        // Cari invoice berdasarkan order_id (yang diisi dengan invoice_number)
        $invoice = Invoice::where('invoice_number', $orderId)->first();

        if (!$invoice) {
            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        // Update status berdasarkan transaction_status Midtrans
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $invoice->status = 'paid';
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $invoice->status = 'failed';
        } else if ($transactionStatus == 'pending') {
            $invoice->status = 'unpaid';
        }

        $invoice->save();

        return response()->json(['status' => 'success']);
    }
}
