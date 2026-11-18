<?php

namespace Tests\Feature;

use App\Events\TransactionCompleted;
use App\Models\MidtransOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_a_pending_midtrans_order_and_reserves_stock(): void
    {
        config([
            'midtrans.server_key' => 'test-server-key',
            'midtrans.client_key' => 'test-client-key',
            'midtrans.is_production' => false,
            'midtrans.snap_redirect' => true,
            'midtrans.is_sanitized' => true,
            'midtrans.is_3ds' => true,
        ]);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct();
        $snap = \Mockery::mock('alias:Midtrans\Snap');
        $snap->shouldReceive('getSnapUrl')
            ->once()
            ->with(\Mockery::on(fn (array $params): bool => $params['transaction_details']['gross_amount'] === 20000))
            ->andReturn('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');

        $response = $this->actingAs($cashier)->postJson(route('kasir.midtrans.checkout'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect_url', 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token');

        $transaction = Transaction::with('midtransOrder')->firstOrFail();
        $this->assertSame('held', $transaction->status);
        $this->assertSame('pending', $transaction->midtransOrder->transaction_status);
        $this->assertSame('https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token', $transaction->midtransOrder->redirect_url);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_checkout_requires_both_midtrans_keys(): void
    {
        config([
            'midtrans.server_key' => null,
            'midtrans.client_key' => null,
        ]);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct();

        $response = $this->actingAs($cashier)->postJson(route('kasir.midtrans.checkout'), [
            'items' => [
                ['id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('success', false);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_pending_midtrans_receipt_does_not_offer_print_or_invoice_download(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3);
        $order = $this->createPendingOrder($cashier, $product);

        $response = $this->actingAs($cashier)->get(route('kasir.receipt', $order->transaction_id));

        $response->assertOk()
            ->assertSee('Menunggu konfirmasi pembayaran Midtrans.')
            ->assertDontSee('Cetak Struk (Print)')
            ->assertDontSee('Unduh Invoice PDF');
    }

    public function test_valid_notification_completes_payment_without_login(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3);
        $order = $this->createPendingOrder($cashier, $product);
        $payload = $this->notificationPayload($order, 'settlement');
        Event::fake([TransactionCompleted::class]);
        Storage::fake('local');

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'status' => 'completed',
            'paid_amount' => 20000,
        ]);
        $this->assertDatabaseHas('midtrans_orders', [
            'id' => $order->id,
            'transaction_status' => 'settlement',
        ]);
        Event::assertDispatched(TransactionCompleted::class);
        Storage::disk('local')->assertExists('receipts/'.$order->order_id.'.pdf');
    }

    public function test_invalid_notification_signature_does_not_change_payment(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3);
        $order = $this->createPendingOrder($cashier, $product);
        $payload = $this->notificationPayload($order, 'settlement');
        $payload['signature_key'] = str_repeat('0', 128);

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'status' => 'held',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_signed_notification_with_an_incorrect_amount_does_not_complete_payment(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3);
        $order = $this->createPendingOrder($cashier, $product);
        $payload = $this->notificationPayload($order, 'settlement');
        $payload['gross_amount'] = '10000.00';
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'),
        );

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'status' => 'held',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_failed_notification_releases_stock_only_once(): void
    {
        config(['midtrans.server_key' => 'test-server-key']);

        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3);
        $order = $this->createPendingOrder($cashier, $product);
        $payload = $this->notificationPayload($order, 'expire');

        $this->postJson(route('midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('midtrans.notification'), $payload)->assertOk();

        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_cash_checkout_cannot_mark_a_transaction_as_paid_by_midtrans(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct();

        $response = $this->actingAs($cashier)->postJson(route('kasir.checkout.store'), [
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
            'paid_amount' => 20000,
            'payment_method' => 'Midtrans',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method');
        $this->assertDatabaseCount('transactions', 0);
    }

    private function createProduct(int $stock = 5): Product
    {
        return Product::create([
            'code' => 'PRD-MT-001',
            'name' => 'Produk Midtrans',
            'price' => 10000,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function createPendingOrder(User $cashier, Product $product): MidtransOrder
    {
        $transaction = Transaction::create([
            'invoice_number' => 'TRX-MT-'.uniqid(),
            'user_id' => $cashier->id,
            'subtotal' => 20000,
            'discount_percent' => 0,
            'discount_nominal' => 0,
            'total_discount' => 0,
            'tax_percent' => 0,
            'total_tax' => 0,
            'other_cost' => 0,
            'grand_total' => 20000,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_method' => 'Midtrans',
            'status' => 'held',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'product_name' => $product->name,
            'price' => 10000,
            'qty' => 2,
            'subtotal' => 20000,
        ]);

        return MidtransOrder::create([
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->invoice_number,
            'transaction_status' => 'pending',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function notificationPayload(MidtransOrder $order, string $status): array
    {
        $statusCode = '200';
        $grossAmount = '20000.00';

        return [
            'order_id' => $order->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => hash(
                'sha512',
                $order->order_id.$statusCode.$grossAmount.config('midtrans.server_key'),
            ),
            'transaction_status' => $status,
        ];
    }
}
