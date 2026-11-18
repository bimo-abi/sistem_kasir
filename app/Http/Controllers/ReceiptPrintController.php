<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ReceiptPrinter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptPrintController extends Controller
{
    public function download(Transaction $transaction, ReceiptPrinter $receiptPrinter): StreamedResponse
    {
        abort_unless($transaction->status === 'completed', 404);

        $path = $receiptPrinter->generatePdf($transaction);

        return Storage::disk('local')->download($path, $transaction->invoice_number.'.pdf');
    }
}
