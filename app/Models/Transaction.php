<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'subtotal',
        'discount_percent',
        'discount_nominal',
        'total_discount',
        'tax_percent',
        'total_tax',
        'other_cost',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_nominal' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke Kasir/User pemproses transaksi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Detail Item Transaksi.
     */
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Format Rupiah Grand Total.
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->grand_total, 0, ',', '.');
    }
}
