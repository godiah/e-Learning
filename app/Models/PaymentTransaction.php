<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'transaction_id',
        'tracking_id',
        'reference',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payment_data',
        'ipn_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_data' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the order that this transaction belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user that this transaction belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
