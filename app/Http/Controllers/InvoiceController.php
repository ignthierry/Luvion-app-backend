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

        $serverKey = config('services.midtrans.server_key');
        if (empty($serverKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Midtrans Server Key tidak terbaca. Harap pastikan kunci serverMidtrans dimasukkan dengan benar di .env'
            ], 500);
        }

        // Set Midtrans configuration
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $midtransOrderId = $invoice->invoice_number . '-' . time();

        $params = array(
            'transaction_details' => array(
                'order_id' => $midtransOrderId,
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
                'midtrans_order_id' => $midtransOrderId,
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
        if ($request->isMethod('get')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Endpoint Webhook Midtrans Aktif. Midtrans akan mengirimkan sinyal notifikasi melalui HTTP POST.'
            ], 200);
        }

        $payload = $request->all();

        // 1. Tangani jika payload kosong (misal ping dasar dari Midtrans)
        if (empty($payload) || !isset($payload['order_id'])) {
            return response()->json([
                'status' => 'success',
                'message' => 'Midtrans Webhook Test Ping Received'
            ], 200);
        }

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKeyIn = $payload['signature_key'] ?? '';
        $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        $transactionStatus = $payload['transaction_status'] ?? '';

        // 2. Jika ini adalah tes otomatis dari tombol 'Test Notification URL' di Dashboard Midtrans
        if (str_contains(strtolower($orderId), 'test') || empty($signatureKeyIn)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Test Notification URL successful'
            ], 200);
        }

        // 3. Verifikasi Signature Key untuk rilis transaksi asli
        if (!empty($serverKey) && !empty($signatureKeyIn)) {
            $calculatedSignature = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);
            if ($calculatedSignature !== $signatureKeyIn && !str_contains(strtolower($orderId), 'test')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid signature'
                ], 403);
            }
        }

        // 4. Cari invoice berdasarkan midtrans_order_id atau invoice_number
        $invoice = Invoice::where('midtrans_order_id', $orderId)
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
            // Jika dummy order_id dari tes Midtrans tidak ada di DB, tetap kembalikan 200 OK
            return response()->json([
                'status' => 'success',
                'message' => 'Notification received for ' . $orderId
            ], 200);
        }

        // 5. Update status berdasarkan transaction_status Midtrans
        $paymentType = $payload['payment_type'] ?? null;
        if ($paymentType) {
            $invoice->payment_type = $paymentType;
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->save();

            if ($invoice->clientOrder) {
                $invoice->clientOrder->payment_status = 'paid';
                $invoice->clientOrder->save();
            }
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $invoice->status = 'failed';
            $invoice->save();
        } else if ($transactionStatus == 'pending') {
            $invoice->status = 'unpaid';
            $invoice->save();
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function checkStatus($id)
    {
        $invoice = Invoice::findOrFail($id);
        $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY'));

        if (empty($serverKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Midtrans Server Key belum dikonfigurasi di file .env'
            ], 400);
        }

        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);

        try {
            $targetOrderId = $invoice->midtrans_order_id ?? $invoice->invoice_number;
            $midtransStatus = \Midtrans\Transaction::status($targetOrderId);
            $transactionStatus = is_object($midtransStatus) ? $midtransStatus->transaction_status : ($midtransStatus['transaction_status'] ?? '');
            $paymentType = is_object($midtransStatus) ? ($midtransStatus->payment_type ?? null) : ($midtransStatus['payment_type'] ?? null);

            if ($paymentType) {
                $invoice->payment_type = $paymentType;
            }

            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->save();

                if ($invoice->clientOrder) {
                    $invoice->clientOrder->payment_status = 'paid';
                    $invoice->clientOrder->save();
                }
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
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
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), "404")) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaksi belum diinisiasi di Midtrans. Silakan buka link pembayaran terlebih dahulu dan pilih metode pembayaran.'
                ], 400);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal sinkronisasi dari Midtrans: ' . $e->getMessage()
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

        // Format Nomor Telepon ke format WhatsApp chatId (misal 6281357748559@c.us)
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

        $message = "Halo {$order->full_name},\n\n"
                 . "Berikut adalah tagihan untuk layanan Luvion SaaS ({$order->plan_name}).\n\n"
                 . "No. Invoice: {$invoice->invoice_number}\n"
                 . "Total Tagihan: Rp {$amountFormatted}\n"
                 . "Tanggal Jatuh Tempo: {$dueDateFormatted}\n\n"
                 . "Silakan lakukan pembayaran melalui link berikut:\n"
                 . "{$invoice->payment_url}\n\n"
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
