<?php

namespace Tests\Feature\Http\Controllers;

use App\Events\TransactionCompleted;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_the_live_transaction_notification_container(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $this->withoutVite();

        $response = $this->actingAs($cashier)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('id="transaction-toast-container"', false);
    }

    public function test_successful_checkout_persists_transaction_and_broadcasts_notification(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'code' => 'PRD-001',
            'name' => 'Kopi',
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
        ]);
        Event::fake([TransactionCompleted::class]);
        Storage::fake('local');

        $response = $this->actingAs($cashier)->postJson(route('kasir.checkout.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
            'paid_amount' => 25000,
            'payment_method' => 'Tunai',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('receipt_printed', true)
            ->assertJsonPath('redirect_url', route('kasir.receipt', $response->json('transaction_id')));

        $transaction = Transaction::findOrFail($response->json('transaction_id'));
        Storage::disk('local')->assertExists('receipts/'.$transaction->invoice_number.'.pdf');
        $this->assertSame('20000.00', $transaction->grand_total);
        $this->assertSame('5000.00', $transaction->change_amount);
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);

        Event::assertDispatched(TransactionCompleted::class, function (TransactionCompleted $event) use ($transaction): bool {
            return $event->broadcastWith() === [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'grand_total' => '20000.00',
            ] && $event->broadcastOn()[0]->name === 'private-transactions';
        });
    }

    public function test_checkout_with_insufficient_payment_does_not_save_or_broadcast_transaction(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'code' => 'PRD-002',
            'name' => 'Teh',
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
        ]);
        Event::fake([TransactionCompleted::class]);

        $response = $this->actingAs($cashier)->postJson(route('kasir.checkout.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
            'paid_amount' => 1000,
            'payment_method' => 'Tunai',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Uang dibayar (Rp 1.000) kurang dari Total Akhir (Rp 20.000).');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
        Event::assertNotDispatched(TransactionCompleted::class);
    }
}
