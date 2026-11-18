<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use RuntimeException;

class ReceiptPrinter
{
    public function print(Transaction $transaction): ?string
    {
        $transaction->loadMissing(['details', 'user']);

        return match (config('receipt.printer')) {
            'pdf' => $this->generatePdf($transaction),
            'windows' => $this->printToWindowsPrinter($transaction),
            default => throw new InvalidArgumentException('Driver printer struk tidak didukung.'),
        };
    }

    public function generatePdf(Transaction $transaction): string
    {
        $transaction->loadMissing(['details', 'user']);
        $path = 'receipts/'.$transaction->invoice_number.'.pdf';
        $height = max(500, 300 + ($transaction->details->count() * 52));
        $pdf = Pdf::loadView('kasir.receipt-pdf', compact('transaction'))
            ->setPaper([0, 0, 226.77, $height]);

        if (! Storage::disk('local')->put($path, $pdf->output())) {
            throw new RuntimeException('PDF struk gagal disimpan ke penyimpanan lokal.');
        }

        return $path;
    }

    private function printToWindowsPrinter(Transaction $transaction): ?string
    {
        $printerName = config('receipt.printer_name');

        if (! is_string($printerName) || trim($printerName) === '') {
            throw new InvalidArgumentException('Nama printer Windows belum dikonfigurasi.');
        }

        $printer = new Printer(new WindowsPrintConnector($printerName));

        try {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text(config('receipt.store_name')."\n");
            $printer->setEmphasis(false);
            $printer->text(config('receipt.store_address')."\n");
            $printer->text(config('receipt.store_phone')."\n");
            $printer->text("--------------------------------\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($transaction->invoice_number."\n");
            $printer->text($transaction->created_at->format('d/m/Y H:i:s')."\n");
            $printer->text("--------------------------------\n");

            foreach ($transaction->details as $detail) {
                $printer->text($detail->product_name."\n");
                $printer->text(
                    $detail->qty.' x Rp '.number_format($detail->price, 0, ',', '.')
                    .' = Rp '.number_format($detail->subtotal, 0, ',', '.')."\n",
                );
            }

            $printer->text("--------------------------------\n");
            $printer->text('Subtotal: Rp '.number_format($transaction->subtotal, 0, ',', '.')."\n");

            if ($transaction->total_discount > 0) {
                $printer->text('Diskon: Rp '.number_format($transaction->total_discount, 0, ',', '.')."\n");
            }

            $printer->setEmphasis(true);
            $printer->text('TOTAL: Rp '.number_format($transaction->grand_total, 0, ',', '.')."\n");
            $printer->setEmphasis(false);
            $printer->text('Dibayar: Rp '.number_format($transaction->paid_amount, 0, ',', '.')."\n");
            $printer->text('Kembalian: Rp '.number_format($transaction->change_amount, 0, ',', '.')."\n");
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Terima kasih telah berbelanja!\n");
            $printer->cut();
        } finally {
            $printer->close();
        }

        return null;
    }
}
