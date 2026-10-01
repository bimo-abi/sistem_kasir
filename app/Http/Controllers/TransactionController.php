<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    /**
     * Simpan Transaksi Pembayaran POS (Status: Completed, Atomic DB Transaction).
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_nominal' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string'],
        ]);

        try {
            $transaction = DB::transaction(function () use ($request) {
                $subtotal = 0;
                $detailsData = [];

                // 1. Validasi Stok & Hitung Ulang Subtotal Backend
                foreach ($request->items as $item) {
                    $product = Product::where('id', $item['id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$product || !$product->is_active) {
                        throw new Exception("Produk \"{$item['name']}\" tidak aktif atau tidak ditemukan.");
                    }

                    if ($product->stock < $item['qty']) {
                        throw new Exception("Stok produk \"{$product->name}\" kurang! Tersisa: {$product->stock}, diminta: {$item['qty']}.");
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

                // 2. Perhitungan Rumus PDF di Backend
                $discPercent = max(0, min(100, floatval($request->discount_percent)));
                $discNominal = max(0, floatval($request->discount_nominal));
                $totalDiscount = ($subtotal * ($discPercent / 100)) + $discNominal;

                $taxPercent = max(0, min(100, floatval($request->tax_percent)));
                $totalTax = max(0, ($subtotal - $totalDiscount) * ($taxPercent / 100));

                $otherCost = max(0, floatval($request->other_cost));
                $grandTotal = max(0, $subtotal - $totalDiscount + $totalTax + $otherCost);

                $paidAmount = floatval($request->paid_amount);

                if ($paidAmount < $grandTotal) {
                    throw new Exception("Uang dibayar (Rp " . number_format($paidAmount, 0, ',', '.') . ") kurang dari Total Akhir (Rp " . number_format($grandTotal, 0, ',', '.') . ").");
                }

                $changeAmount = $paidAmount - $grandTotal;

                // 3. Simpan Header Transaksi
                $invoiceNumber = 'TRX-' . date('Ymd') . '-' . rand(1000, 9999);
                $trx = Transaction::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => Auth::id(),
                    'subtotal' => $subtotal,
                    'discount_percent' => $discPercent,
                    'discount_nominal' => $discNominal,
                    'total_discount' => $totalDiscount,
                    'tax_percent' => $taxPercent,
                    'total_tax' => $totalTax,
                    'other_cost' => $otherCost,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $paidAmount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $request->payment_method,
                    'status' => 'completed',
                ]);

                // 4. Simpan Detail Transaksi & Pengurangan Stok Atomik
                foreach ($detailsData as $d) {
                    TransactionDetail::create([
                        'transaction_id' => $trx->id,
                        'product_id' => $d['product']->id,
                        'product_code' => $d['product']->code,
                        'product_name' => $d['product']->name,
                        'price' => $d['price'],
                        'qty' => $d['qty'],
                        'subtotal' => $d['subtotal'],
                    ]);

                    // Pengurangan stok permanen
                    $d['product']->decrement('stock', $d['qty']);
                }

                return $trx;
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan!',
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'redirect_url' => route('kasir.receipt', $transaction->id),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Simpan Transaksi Ditahan (Status: Held).
     */
    public function hold(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $transaction = DB::transaction(function () use ($request) {
                $subtotal = 0;
                $detailsData = [];

                foreach ($request->items as $item) {
                    $product = Product::find($item['id']);
                    $itemSubtotal = $product->price * $item['qty'];
                    $subtotal += $itemSubtotal;

                    $detailsData[] = [
                        'product' => $product,
                        'qty' => $item['qty'],
                        'price' => $product->price,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $discPercent = max(0, min(100, floatval($request->discount_percent)));
                $discNominal = max(0, floatval($request->discount_nominal));
                $totalDiscount = ($subtotal * ($discPercent / 100)) + $discNominal;

                $taxPercent = max(0, min(100, floatval($request->tax_percent)));
                $totalTax = max(0, ($subtotal - $totalDiscount) * ($taxPercent / 100));

                $otherCost = max(0, floatval($request->other_cost));
                $grandTotal = max(0, $subtotal - $totalDiscount + $totalTax + $otherCost);

                $invoiceNumber = 'HLD-' . date('Ymd') . '-' . rand(1000, 9999);
                $trx = Transaction::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => Auth::id(),
                    'subtotal' => $subtotal,
                    'discount_percent' => $discPercent,
                    'discount_nominal' => $discNominal,
                    'total_discount' => $totalDiscount,
                    'tax_percent' => $taxPercent,
                    'total_tax' => $totalTax,
                    'other_cost' => $otherCost,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'change_amount' => 0,
                    'payment_method' => $request->payment_method ?? 'Tunai',
                    'status' => 'held',
                ]);

                foreach ($detailsData as $d) {
                    TransactionDetail::create([
                        'transaction_id' => $trx->id,
                        'product_id' => $d['product']->id,
                        'product_code' => $d['product']->code,
                        'product_name' => $d['product']->name,
                        'price' => $d['price'],
                        'qty' => $d['qty'],
                        'subtotal' => $d['subtotal'],
                    ]);
                }

                return $trx;
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditahan!',
                'invoice_number' => $transaction->invoice_number,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Tampilkan Daftar Transaksi Ditahan.
     */
    public function heldIndex()
    {
        $heldTransactions = Transaction::with('details')
            ->where('status', 'held')
            ->latest()
            ->get();

        return view('kasir.held', compact('heldTransactions'));
    }

    /**
     * Ambil detail transaksi ditahan dalam bentuk JSON untuk dibuka kembali di POS.
     */
    public function resumeHeld($id)
    {
        $transaction = Transaction::with('details.product')
            ->where('status', 'held')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Batalkan Transaksi Ditahan.
     */
    public function cancelHeld($id)
    {
        $transaction = Transaction::where('status', 'held')->findOrFail($id);
        $transaction->update(['status' => 'cancelled']);

        return redirect()->route('kasir.held.index')
            ->with('success', 'Transaksi ditahan berhasil dibatalkan.');
    }

    /**
     * Tampilkan Struk Pembayaran Kasir (Thermal Receipt View).
     */
    public function receipt($id)
    {
        $transaction = Transaction::with(['details', 'user'])->findOrFail($id);
        return view('kasir.receipt', compact('transaction'));
    }

    /**
     * Tampilkan Riwayat Transaksi Penjualan.
     */
    public function history(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $transactions = Transaction::with(['user', 'details'])
            ->where('status', 'completed')
            ->when($search, function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%");
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('kasir.history', compact('transactions', 'search', 'startDate', 'endDate'));
    }
}
