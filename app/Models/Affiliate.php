<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'payment_info',
        'status',
        'total_earnings',
        'total_sales',
        'commission_rate',
    ];

    protected $casts = [
        'total_earnings' => 'decimal:2',
        'commission_rate' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function link()
    {
        return $this->hasOne(AffiliateLink::class);
    }

    public function purchases()
    {
        return $this->hasMany(AffiliatePurchase::class);
    }
}
