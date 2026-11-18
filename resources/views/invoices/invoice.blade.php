<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $transaction->invoice_number }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 11px; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 18px; }
        .store { font-size: 19px; font-weight: bold; color: #1d4ed8; margin: 0 0 5px; }
        .muted { color: #666; }
        .right { text-align: right; }
        .invoice-title { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .items { margin-top: 20px; }
        .items th { background: #1d4ed8; color: white; text-align: left; padding: 9px 7px; }
        .items td { border-bottom: 1px solid #ddd; padding: 8px 7px; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .totals { width: 54%; margin-left: auto; margin-top: 15px; }
        .totals td { padding: 5px 7px; }
        .grand { background: #eff6ff; color: #1d4ed8; font-weight: bold; font-size: 13px; border-top: 2px solid #1d4ed8; }
        .footer { margin-top: 35px; border-top: 1px solid #ddd; padding-top: 12px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <table><tr>
            <td>
                <div class="store">MINIMARKET SEJAHTERA</div>
                <div class="muted">Sistem Kasir - Invoice Penjualan</div>
                <div class="muted">Dokumen invoice dibuat secara otomatis dari data transaksi.</div>
            </td>
            <td class="right"><div class="invoice-title">INVOICE</div><strong>{{ $transaction->invoice_number }}</strong></td>
        </tr></table>
    </div>

    <table class="meta">
        <tr><td width="18%" class="muted">Nomor Invoice</td><td width="42%">: {{ $transaction->invoice_number }}</td><td width="18%" class="muted">Tanggal</td><td>: {{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="muted">Kasir</td><td>: {{ $transaction->user->name ?? '-' }}</td><td class="muted">Pembayaran</td><td>: {{ $transaction->payment_method }}</td></tr>
        <tr><td class="muted">Status</td><td>: {{ strtoupper($transaction->status) }}</td><td class="muted">ID Transaksi</td><td>: {{ $transaction->id }}</td></tr>
    </table>

    <table class="items">
        <thead><tr><th width="6%" class="center">No.</th><th>Nama Produk</th><th width="16%">Kode</th><th width="10%" class="center">Qty</th><th width="18%" class="num">Harga</th><th width="20%" class="num">Subtotal</th></tr></thead>
        <tbody>
        @foreach($transaction->details as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td><td>{{ $item->product_name }}</td><td>{{ $item->product_code }}</td><td class="center">{{ $item->qty }}</td>
                <td class="num">Rp {{ number_format($item->price, 0, ',', '.') }}</td><td class="num">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
        @if($transaction->total_discount > 0)<tr><td>Total Diskon</td><td class="num">- Rp {{ number_format($transaction->total_discount, 0, ',', '.') }}</td></tr>@endif
        @if($transaction->total_tax > 0)<tr><td>Pajak / PPN ({{ (float) $transaction->tax_percent }}%)</td><td class="num">Rp {{ number_format($transaction->total_tax, 0, ',', '.') }}</td></tr>@endif
        @if($transaction->other_cost > 0)<tr><td>Biaya Lain</td><td class="num">Rp {{ number_format($transaction->other_cost, 0, ',', '.') }}</td></tr>@endif
        <tr class="grand"><td>TOTAL AKHIR</td><td class="num">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td></tr>
        <tr><td>Dibayar</td><td class="num">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Kembalian</td><td class="num">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td></tr>
    </table>

    <div class="footer">
        <strong>Terima kasih telah berbelanja di Minimarket Sejahtera.</strong><br>
        Invoice ini dihasilkan otomatis oleh sistem kasir.
    </div>
</body>
</html>
