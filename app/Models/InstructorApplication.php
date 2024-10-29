<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructorApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'headline', 'bio', 'profile_pic_url', 'terms_accepted', 'payment_method', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
