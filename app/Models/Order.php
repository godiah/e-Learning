<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'discount_total',
        'final_amount',
        'payment_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'final_amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function enrollments()
    {
        return $this->hasManyThrough(
            Enrollment::class,
            OrderItem::class,
            'order_id', // Foreign key on order_items table
            'course_id', // Foreign key on enrollments table
            'id', // Local key on orders table
            'course_id' // Local key on order_items table
        );
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function affiliatePurchases()
    {
        return $this->hasMany(AffiliatePurchase::class);
    }
}
