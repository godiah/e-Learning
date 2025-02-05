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
    ];

    protected $casts = [
        'total_earnings' => 'decimal:2',        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function links()
    {
        return $this->hasMany(AffiliateLink::class);
    }

    public function conversions()
    {
        return $this->hasManyThrough(ConversionTracking::class, AffiliateLink::class);
    }

    public function commissions() {
        return $this->hasMany(Commission::class); 
    }
}
