<?php

namespace App\Http\Controllers;

use App\Events\TransactionCompleted;
use App\Models\MidtransOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReceiptPrinter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class MidtransController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        if (! filled(config('midtrans.server_key')) || ! filled(config('midtrans.client_key'))) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY belum tersedia.',
            ], 503);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_nominal' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $transaction = DB::transaction(function () use ($validated): Transaction {
            $items = collect($validated['items']);
            $products = Product::query()
                ->whereIn('id', $items->pluck('id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $subtotal = 0;
            $detailsData = [];

            foreach ($items as $item) {
                $product = $products->get($item['id']);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk tidak aktif atau tidak ditemukan.',
                    ]);
                }

                if ($product->stock < $item['qty']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} tidak mencukupi.",
                    ]);
                }

                $itemSubtotal = $product->price * $item['qty'];
                $subtotal += $itemSubtotal;
                $detailsData[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $discountPercent = (float) ($validated['discount_percent'] ?? 0);
            $discountNominal = (float) ($validated['discount_nominal'] ?? 0);
            $totalDiscount = ($subtotal * ($discountPercent / 100)) + $discountNominal;
            $taxPercent = (float) ($validated['tax_percent'] ?? 0);
            $totalTax = max(0, ($subtotal - $totalDiscount) * ($taxPercent / 100));
            $otherCost = (float) ($validated['other_cost'] ?? 0);
            $grandTotal = max(0, round($subtotal - $totalDiscount + $totalTax + $otherCost));

            if ($grandTotal < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Total pembayaran Midtrans harus lebih dari Rp 0.',
                ]);
            }

            $transaction = Transaction::create([
                'invoice_number' => 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_nominal' => $discountNominal,
                'total_discount' => $totalDiscount,
                'tax_percent' => $taxPercent,
                'total_tax' => $totalTax,
                'other_cost' => $otherCost,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_method' => 'Midtrans',
                'status' => 'held',
            ]);

            foreach ($detailsData as $detail) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $detail['product']->id,
                    'product_code' => $detail['product']->code,
                    'product_name' => $detail['product']->name,
                    'price' => $detail['price'],
                    'qty' => $detail['qty'],
                    'subtotal' => $detail['subtotal'],
                ]);

                $detail['product']->decrement('stock', $detail['qty']);
            }

            MidtransOrder::create([
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->invoice_number,
                'transaction_status' => 'pending',
            ]);

            return $transaction->load(['details', 'user', 'midtransOrder']);
        });

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->midtransOrder->order_id,
                'gross_amount' => (int) $transaction->grand_total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'callbacks' => [
                'finish' => route('kasir.receipt', $transaction->id),
            ],
        ];

        try {
            if (config('midtrans.snap_redirect')) {
                $redirectUrl = Snap::getSnapUrl($params);
                $transaction->midtransOrder->update(['redirect_url' => $redirectUrl]);

                return response()->json([
                    'success' => true,
                    'order_id' => $transaction->midtransOrder->order_id,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            $snapToken = Snap::getSnapToken($params);
            $transaction->midtransOrder->update(['snap_token' => $snapToken]);

            return response()->json([
                'success' => true,
                'order_id' => $transaction->midtransOrder->order_id,
                'snap_token' => $snapToken,
                'receipt_url' => route('kasir.receipt', $transaction->id),
            ]);
        } catch (Exception $exception) {
            report($exception);
            $this->releaseReservedStock($transaction->midtransOrder);

            return response()->json([
                'success' => false,
                'message' => 'Midtrans gagal membuat sesi pembayaran. Silakan coba kembali.',
            ], 502);
        }
    }

    public function notification(Request $request, ReceiptPrinter $receiptPrinter): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:50'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'numeric'],
            'signature_key' => ['required', 'string', 'regex:/^[a-f0-9]{128}$/i'],
            'transaction_status' => ['required', 'string'],
            'fraud_status' => ['nullable', 'string'],
        ]);

        $serverKey = config('midtrans.server_key');

        if (! filled($serverKey)) {
            return response()->json(['message' => 'Konfigurasi server Midtrans belum tersedia.'], 503);
        }

        $expectedSignature = hash(
            'sha512',
            $validated['order_id'].$validated['status_code'].$validated['gross_amount'].$serverKey,
        );

        if (! hash_equals($expectedSignature, $validated['signature_key'])) {
            return response()->json(['message' => 'Signature notifikasi Midtrans tidak valid.'], 403);
        }

        $result = DB::transaction(function () use ($validated): string {
            $midtransOrder = MidtransOrder::query()
                ->where('order_id', $validated['order_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $transaction = Transaction::query()
                ->with('details')
                ->lockForUpdate()
                ->findOrFail($midtransOrder->transaction_id);

            if (round((float) $transaction->grand_total) !== round((float) $validated['gross_amount'])) {
                return 'amount_mismatch';
            }

            $gatewayStatus = $validated['transaction_status'];
            $isPaid = $gatewayStatus === 'settlement'
                || ($gatewayStatus === 'capture' && ($validated['fraud_status'] ?? null) === 'accept');
            $isFailed = in_array($gatewayStatus, ['deny', 'cancel', 'expire', 'failure'], true);

            if ($transaction->status === 'completed') {
                return 'already_completed';
            }

            if ($isPaid) {
                if ($transaction->status === 'cancelled' && ! $this->canReserveStockAgain($transaction)) {
                    return 'stock_unavailable';
                }

                if ($transaction->status === 'cancelled') {
                    $this->reserveStockAgain($transaction);
                }

                $transaction->update([
                    'status' => 'completed',
                    'paid_amount' => $transaction->grand_total,
                    'change_amount' => 0,
                ]);
                $midtransOrder->update(['transaction_status' => $gatewayStatus]);

                return 'completed';
            }

            if ($isFailed && $transaction->status === 'held') {
                $this->restoreStock($transaction);
                $transaction->update(['status' => 'cancelled']);
            }

            $midtransOrder->update(['transaction_status' => $gatewayStatus]);

            return $transaction->status;
        });

        if ($result === 'amount_mismatch') {
            return response()->json(['message' => 'Nominal notifikasi tidak sesuai dengan transaksi.'], 422);
        }

        if ($result === 'stock_unavailable') {
            return response()->json(['message' => 'Stok tidak mencukupi untuk menyelesaikan pembayaran ini.'], 409);
        }

        if (in_array($result, ['completed', 'already_completed'], true)) {
            $transaction = MidtransOrder::where('order_id', $validated['order_id'])
                ->with('transaction')
                ->firstOrFail()
                ->transaction;

            if ($result === 'completed') {
                TransactionCompleted::dispatch(
                    $transaction->id,
                    $transaction->invoice_number,
                    $transaction->grand_total,
                );
            }

            if ($result === 'completed' || config('receipt.printer') === 'pdf') {
                try {
                    $receiptPrinter->print($transaction);
                } catch (Throwable $exception) {
                    report($exception);

                    return response()->json(['message' => 'Pembayaran berhasil, tetapi struk gagal dibuat.'], 500);
                }
            }
        }

        return response()->json(['message' => 'Notification handled']);
    }

    private function canReserveStockAgain(Transaction $transaction): bool
    {
        $productIds = $transaction->details->pluck('product_id');

        if ($productIds->contains(null)) {
            return false;
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($transaction->details as $detail) {
            $product = $products->get($detail->product_id);

            if (! $product || $product->stock < $detail->qty) {
                return false;
            }
        }

        return true;
    }

    private function reserveStockAgain(Transaction $transaction): void
    {
        foreach ($transaction->details as $detail) {
            Product::whereKey($detail->product_id)->decrement('stock', $detail->qty);
        }
    }

    private function restoreStock(Transaction $transaction): void
    {
        foreach ($transaction->details as $detail) {
            if ($detail->product_id !== null) {
                Product::whereKey($detail->product_id)->increment('stock', $detail->qty);
            }
        }
    }

    private function releaseReservedStock(MidtransOrder $midtransOrder): void
    {
        DB::transaction(function () use ($midtransOrder): void {
            $order = MidtransOrder::whereKey($midtransOrder->id)->lockForUpdate()->firstOrFail();
            $transaction = Transaction::with('details')
                ->lockForUpdate()
                ->findOrFail($order->transaction_id);

            if ($transaction->status !== 'held') {
                return;
            }

            $this->restoreStock($transaction);
            $transaction->update(['status' => 'cancelled']);
            $order->update(['transaction_status' => 'failure']);
        });
    }
}
