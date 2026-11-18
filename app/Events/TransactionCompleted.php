<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly int $transactionId,
        private readonly string $invoiceNumber,
        private readonly string $grandTotal,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('transactions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.completed';
    }

    /**
     * @return array{transaction_id: int, invoice_number: string, grand_total: string}
     */
    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'invoice_number' => $this->invoiceNumber,
            'grand_total' => $this->grandTotal,
        ];
    }
}
