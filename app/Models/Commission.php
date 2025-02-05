<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = ['affiliate_id', 'conversion_id', 'commission_amount', 'status', 'paid_at'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function affiliate() { 
        return $this->belongsTo(Affiliate::class); 
    }

    public function conversion() { 
        return $this->belongsTo(ConversionTracking::class); 
    }
}
