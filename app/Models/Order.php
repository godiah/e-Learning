<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Remove affiliate_code and add payment tracking ID

    protected $fillable = [
        'user_id',
        'total_amount',
        'discount_total',
        'final_amount',
        'affiliate_code',
        //'payment_id'
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
    
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
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
