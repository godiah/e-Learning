<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionTracking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'conversion_tracking';

    protected $fillable = ['affiliate_link_id','click_tracking_id', 'order_id', 'sale_amount','converted_at'];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function affiliateLink()
    {
        return $this->belongsTo(AffiliateLink::class, 'affiliate_link_id');
    }

    public function commission()
    {
        return $this->hasOne(Commission::class);
    }
}
