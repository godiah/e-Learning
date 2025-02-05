<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'course_id',
        'price',
        'discount_amount',
        'final_price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }
}
