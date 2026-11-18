<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\ReceiptPrinter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptPrinterTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_transaction_is_rendered_and_downloaded_as_a_pdf_receipt(): void
    {
        config(['receipt.printer' => 'pdf']);
        Storage::fake('local');

        $cashier = User::factory()->create(['role' => 'kasir']);
        $transaction = Transaction::create([
            'invoice_number' => 'TRX-PDF-TEST',
            'user_id' => $cashier->id,
            'subtotal' => 20000,
            'discount_percent' => 0,
            'discount_nominal' => 0,
            'total_discount' => 0,
            'tax_percent' => 0,
            'total_tax' => 0,
            'other_cost' => 0,
            'grand_total' => 20000,
            'paid_amount' => 20000,
            'change_amount' => 0,
            'payment_method' => 'Tunai',
            'status' => 'completed',
        ]);
        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => null,
            'product_code' => 'PRD-PDF',
            'product_name' => 'Produk PDF',
            'price' => 10000,
            'qty' => 2,
            'subtotal' => 20000,
        ]);

        $path = app(ReceiptPrinter::class)->print($transaction);

        $this->assertSame('receipts/TRX-PDF-TEST.pdf', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($path));

        $response = $this->actingAs($cashier)->get(route('kasir.receipt.pdf', $transaction->id));

        $response->assertOk()
            ->assertDownload('TRX-PDF-TEST.pdf');
    }
}
