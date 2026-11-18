<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 7px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
    </style>
</head>
<body>
    <div class="center">
        <div class="bold">{{ config('receipt.store_name') }}</div>
        <div>{{ config('receipt.store_address') }}</div>
        <div>{{ config('receipt.store_phone') }}</div>
    </div>
    <div class="line"></div>
    <div>{{ $transaction->invoice_number }}</div>
    <div>{{ $transaction->created_at->format('d/m/Y H:i:s') }} | Kasir: {{ $transaction->user->name }}</div>
    <div class="line"></div>

    <table>
        @foreach($transaction->details as $item)
            <tr><td colspan="2" class="bold">{{ $item->product_name }}</td></tr>
            <tr>
                <td>{{ $item->qty }} x Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>
    <table>
        <tr><td>Subtotal</td><td class="right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr>
        @if($transaction->total_discount > 0)
            <tr><td>Diskon</td><td class="right">- Rp {{ number_format($transaction->total_discount, 0, ',', '.') }}</td></tr>
        @endif
        @if($transaction->total_tax > 0)
            <tr><td>Pajak / PPN</td><td class="right">Rp {{ number_format($transaction->total_tax, 0, ',', '.') }}</td></tr>
        @endif
        @if($transaction->other_cost > 0)
            <tr><td>Biaya lain</td><td class="right">Rp {{ number_format($transaction->other_cost, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="bold"><td>TOTAL</td><td class="right">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td></tr>
        <tr><td>Dibayar</td><td class="right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Kembalian</td><td class="right">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td></tr>
    </table>
    <div class="line"></div>
    <div class="center">Terima kasih telah berbelanja!</div>
</body>
</html>
