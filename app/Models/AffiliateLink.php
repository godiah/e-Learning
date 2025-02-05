<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'course_id',
        'tracking_code',
        'short_url',
        'commission_rate',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2'
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function commissions()
    {
        return $this->hasManyThrough(
            Commission::class, 
            ConversionTracking::class, 
            'affiliate_link_id', // Foreign key on conversion_tracking table...
            'conversion_id',     // Foreign key on commissions table...
            'id',                // Local key on affiliate_links table...
            'id'                 // Local key on conversion_tracking table...
        );
    }

    public function clicks() { 
        return $this->hasMany(ClickTracking::class, 'affiliate_link_id'); 
    }

    public function conversions()
    {
        return $this->hasMany(ConversionTracking::class, 'affiliate_link_id');
    }
}
