<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliatePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'user_id',
        'order_id',
        'amount',
        'commission',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
