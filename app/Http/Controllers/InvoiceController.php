<?php

namespace App\Http\Controllers;

use App\Models\ClientOrder;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:1000',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $amount = isset($validated['amount']) && $validated['amount'] > 0 
            ? $validated['amount'] 
            : ($order->pricing_payment ?: 0);

        if (!$amount || $amount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Harap masukkan nominal tagihan yang valid (minimal Rp 1.000).'
            ], 422);
        }

        $description = !empty($validated['description']) 
            ? $validated['description'] 
            : "Langganan {$order->plan_name} ({$order->billing_cycle})";

        $dueDate = !empty($validated['due_date']) 
            ? $validated['due_date'] 
            : ($order->billing_due_day ? date('Y-m-') . str_pad($order->billing_due_day, 2, '0', STR_PAD_LEFT) : date('Y-m-d', strtotime('+7 days')));

        // Generate Invoice Number (INV-YYYYMM-ORDERID-X)
        $yearMonth = date('Ym');
        $count = Invoice::where('client_order_id', $orderId)->count() + 1;
        $invoiceNumber = 'INV-' . $yearMonth . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'client_order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'description' => $description,
            'status' => 'unpaid',
            'due_date' => $dueDate
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

        $secretKey = config('services.xendit.secret_key');
        if (empty($secretKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Xendit Secret Key tidak terbaca. Harap pastikan kunci dimasukkan dengan benar di .env'
            ], 500);
        }

        \Xendit\Configuration::setXenditKey($secretKey);
        
        $apiInstance = new \Xendit\Invoice\InvoiceApi();

        $xenditOrderId = $invoice->invoice_number . '-' . time();
        $itemDescription = $invoice->description ?: "Langganan {$order->plan_name} ({$order->billing_cycle})";

        $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => $xenditOrderId,
            'amount' => (int) $invoice->amount,
            'description' => substr($itemDescription, 0, 255),
            'payer_email' => $order->email,
            'customer' => [
                'given_names' => $order->full_name,
                'email' => $order->email,
                'mobile_number' => $order->phone,
            ],
            'items' => [
                [
                    'name' => substr($itemDescription, 0, 50),
                    'quantity' => 1,
                    'price' => (int) $invoice->amount
                ]
            ]
        ]);

        try {
            $result = $apiInstance->createInvoice($create_invoice_request);
            $paymentUrl = $result['invoice_url'];
            
            $invoice->update([
                'xendit_id' => $xenditOrderId,
                'snap_token' => $result['id'],
                'payment_url' => $paymentUrl
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Link pembayaran Xendit berhasil dibuat.',
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

    public function handleWebhook(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Endpoint Webhook Xendit Aktif.'
            ], 200);
        }

        // Verify Xendit Webhook Token
        $xenditXCallbackToken = $request->header('x-callback-token');
        $expectedToken = config('services.xendit.webhook_token', env('XENDIT_WEBHOOK_TOKEN'));
        
        if ($expectedToken && $xenditXCallbackToken !== $expectedToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Callback Token'
            ], 403);
        }

        $payload = $request->all();

        // 1. Check if payload is valid
        if (empty($payload) || !isset($payload['external_id'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Webhook Test Received'
            ], 200);
        }

        $orderId = $payload['external_id'] ?? '';
        $transactionStatus = $payload['status'] ?? '';
        $paymentType = $payload['payment_method'] ?? null;
        
        // 4. Cari invoice berdasarkan xendit_id atau invoice_number
        $invoice = Invoice::where('xendit_id', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        if (!$invoice && str_contains($orderId, '-')) {
            $parts = explode('-', $orderId);
            if (count($parts) >= 4) {
                $baseInvoiceNumber = implode('-', array_slice($parts, 0, 4));
                $invoice = Invoice::where('invoice_number', $baseInvoiceNumber)->first();
            }
        }

        if (!$invoice) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notification received for ' . $orderId
            ], 200);
        }

        if ($paymentType) {
            $invoice->payment_type = $paymentType;
        }

        if ($transactionStatus == 'PAID' || $transactionStatus == 'SETTLED') {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->save();

            if ($invoice->clientOrder) {
                $invoice->clientOrder->payment_status = 'paid';
                $invoice->clientOrder->save();
            }

            // Automatic accounting: Debit Bank (or Receivable), Credit Revenue.
            \App\Services\AccountingService::postRevenueFromInvoice($invoice);
        } else if ($transactionStatus == 'EXPIRED') {
            $invoice->status = 'failed';
            $invoice->save();
        } else if ($transactionStatus == 'PENDING') {
            $invoice->status = 'unpaid';
            $invoice->save();
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function checkStatus($id)
    {
        $invoice = Invoice::findOrFail($id);
        $secretKey = config('services.xendit.secret_key');

        if (empty($secretKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Xendit Secret Key belum dikonfigurasi di file .env'
            ], 400);
        }

        \Xendit\Configuration::setXenditKey($secretKey);
        
        $apiInstance = new \Xendit\Invoice\InvoiceApi();

        try {
            $xenditInvoiceId = $invoice->snap_token;
            if (!$xenditInvoiceId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID Invoice Xendit tidak ditemukan. Silakan buat ulang link pembayaran.'
                ], 400);
            }
            
            $xenditInvoice = $apiInstance->getInvoiceById($xenditInvoiceId);
            $transactionStatus = $xenditInvoice['status'] ?? '';
            $paymentType = $xenditInvoice['payment_method'] ?? null;

            if ($paymentType) {
                $invoice->payment_type = $paymentType;
            }

            if ($transactionStatus == 'PAID' || $transactionStatus == 'SETTLED') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->save();

                if ($invoice->clientOrder) {
                    $invoice->clientOrder->payment_status = 'paid';
                    $invoice->clientOrder->save();
                }
            } else if ($transactionStatus == 'EXPIRED') {
                $invoice->status = 'failed';
                $invoice->save();
            }

            return response()->json([
                'status' => 'success',
                'transaction_status' => $transactionStatus,
                'invoice_status' => $invoice->status,
                'message' => 'Status berhasil diperbarui: ' . strtoupper($invoice->status)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal sinkronisasi dari Xendit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendWhatsApp($id)
    {
        $invoice = Invoice::with('clientOrder')->findOrFail($id);
        $order = $invoice->clientOrder;

        if (!$order || !$order->phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor telepon/WhatsApp klien tidak ditemukan.'
            ], 400);
        }

        if (empty($invoice->payment_url)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Link pembayaran belum dibuat. Harap buat link pembayaran terlebih dahulu.'
            ], 400);
        }

        // Format Nomor Telepon ke format WhatsApp chatId (misal 628197965599@c.us)
        $phone = preg_replace('/[^0-9]/', '', $order->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $chatId = str_contains($phone, '@') ? $phone : $phone . '@c.us';

        $baseUrl = rtrim(config('services.waha.base_url', env('WAHA_BASE_URL', 'https://waha.luvion.my.id')), '/');
        $apiKey = config('services.waha.api_key', env('WAHA_API_KEY', '8c958d8d204f4bc2a510cfe81cbbf903'));
        $session = config('services.waha.session', env('WAHA_SESSION', 'default'));

        $amountFormatted = number_format((float)$invoice->amount, 0, ',', '.');
        $dueDateFormatted = $invoice->due_date ? date('d M Y', strtotime($invoice->due_date)) : '-';

        $frontendUrl = env('FRONTEND_URL', 'https://luvion.my.id');
        $invoiceViewUrl = rtrim($frontendUrl, '/') . "/invoices/{$invoice->id}";

        $message = "Halo {$order->full_name},\n\n"
                 . "Berikut adalah tagihan untuk layanan Luvion SaaS ({$order->plan_name}).\n\n"
                 . "No. Invoice: {$invoice->invoice_number}\n"
                 . "Total Tagihan: Rp {$amountFormatted}\n"
                 . "Tanggal Jatuh Tempo: {$dueDateFormatted}\n\n"
                 . "Silakan lihat detail invoice & lakukan pembayaran melalui link berikut:\n"
                 . "{$invoiceViewUrl}\n\n"
                 . "Terima kasih,\n"
                 . "Tim Luvion";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Api-Key' => $apiKey,
            ])->post("{$baseUrl}/api/sendText", [
                'session' => $session,
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pesan WhatsApp berhasil dikirim langsung via WAHA ke nomor ' . $order->phone
                ]);
            } else {
                $errorData = $response->json();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim pesan via WAHA: ' . ($errorData['message'] ?? $response->body())
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi server WAHA API: ' . $e->getMessage()
            ], 500);
        }
    }
}
