<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MidtransOrder extends Model
{
    protected $fillable = [
        'transaction_id',
        'order_id',
        'transaction_status',
        'snap_token',
        'redirect_url',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
