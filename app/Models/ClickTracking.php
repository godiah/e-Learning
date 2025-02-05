<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClickTracking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'click_tracking';

    protected $fillable = ['affiliate_link_id','ip_address','user_agent','clicked_at'];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function link() { 
        return $this->belongsTo(AffiliateLink::class, 'affiliate_link_id'); 
    }

    public function conversion() { 
        return $this->hasOne(ConversionTracking::class); 
    }
}
