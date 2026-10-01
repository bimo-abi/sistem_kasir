<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $transaction->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inconsolata', monospace, sans-serif;
            background-color: #f1f5f9;
            color: #000;
        }
        .receipt-container {
            max-width: 380px;
            margin: 30px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .receipt-title {
            font-weight: 700;
            font-size: 1.25rem;
            text-transform: uppercase;
        }
        .dashed-divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .receipt-table {
            width: 100%;
            font-size: 0.9rem;
        }
        .receipt-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .btn-action-bar {
            max-width: 380px;
            margin: 0 auto 20px auto;
        }

        /* Stylesheet Khusus Cetak Browser (Thermal Printer 58mm / 80mm) */
        @media print {
            body {
                background: #ffffff !important;
                color: #000 !important;
            }
            .btn-action-bar {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Navigasi Cetak & Kembali (Disembunyikan saat Print) -->
    <div class="btn-action-bar pt-4 text-center">
        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ route('kasir.pos') }}" class="btn btn-secondary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Kasir
            </a>
            <button type="button" class="btn btn-primary fw-semibold px-4" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Cetak Struk (Print)
            </button>
        </div>
    </div>

    <!-- Kertas Struk Thermal -->
    <div class="receipt-container">
        <!-- Header Toko -->
        <div class="receipt-header">
            <h5 class="receipt-title mb-1">MINIMARKET SEJAHTERA</h5>
            <div class="fs-7">Jl. Pemuda No. 45, Jakarta Pusat</div>
            <div class="fs-7">Telp: (021) 555-0192</div>
        </div>

        <!-- Info Transaksi -->
        <div class="fs-7 mb-2">
            <div class="d-flex justify-content-between">
                <span>No. Struk:</span>
                <strong class="font-monospace">{{ $transaction->invoice_number }}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Waktu:</span>
                <span>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>Kasir:</span>
                <span>{{ $transaction->user->name }}</span>
            </div>
        </div>

        <div class="dashed-divider"></div>

        <!-- Tabel Item Barang -->
        <table class="receipt-table">
            <tbody>
                @foreach($transaction->details as $item)
                    <tr>
                        <td colspan="3" class="fw-bold">{{ $item->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="width: 40%;" class="ps-2">{{ $item->qty }} x Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="width: 10%;"></td>
                        <td style="width: 50%;" class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="dashed-divider"></div>

        <!-- Total Perhitungan Rumus PDF -->
        <div class="fs-7">
            <div class="d-flex justify-content-between mb-1">
                <span>Subtotal:</span>
                <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>

            @if($transaction->total_discount > 0)
                <div class="d-flex justify-content-between mb-1 text-danger">
                    <span>Total Diskon ({{ (float)$transaction->discount_percent }}% + Rp {{ number_format($transaction->discount_nominal, 0, ',', '.') }}):</span>
                    <span>- Rp {{ number_format($transaction->total_discount, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($transaction->total_tax > 0)
                <div class="d-flex justify-content-between mb-1">
                    <span>Pajak / PPN ({{ (float)$transaction->tax_percent }}%):</span>
                    <span>Rp {{ number_format($transaction->total_tax, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($transaction->other_cost > 0)
                <div class="d-flex justify-content-between mb-1">
                    <span>Biaya Lain:</span>
                    <span>Rp {{ number_format($transaction->other_cost, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="dashed-divider"></div>

            <div class="d-flex justify-content-between mb-1 fs-6 fw-bold">
                <span>TOTAL AKHIR:</span>
                <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Metode Pembayaran:</span>
                <span>{{ $transaction->payment_method }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Dibayar:</span>
                <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1 fw-bold">
                <span>Kembalian:</span>
                <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="dashed-divider"></div>

        <!-- Footer Struk -->
        <div class="text-center fs-7 mt-3">
            <div>*** TERIMA KASIH ***</div>
            <div>BARANG YANG SUDAH DIBELI</div>
            <div>TIDAK DAPAT DITUKAR / DIKEMBALIKAN</div>
        </div>
    </div>

</body>
</html>
