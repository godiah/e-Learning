<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'course_id',
        'price',
        'discount_amount',
        'final_price'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2'
    ];
    
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
    
    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }
}
