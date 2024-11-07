<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'discount_total',
        'final_amount',
        'status'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'final_amount' => 'decimal:2'
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
