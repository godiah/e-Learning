<?php

namespace App\Models;

use App\Helpers\CacheHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'rating', 'comment'];

    protected $casts = [
        'rating' => 'integer',
    ];

        protected static function booted()
    {
        static::created(function ($review) {
            CacheHelper::clearReviewRelatedCaches($review);
        });

        static::updated(function ($review) {
            CacheHelper::clearReviewRelatedCaches($review);
        });

        static::deleted(function ($review) {
            CacheHelper::clearReviewRelatedCaches($review);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Courses::class);
    }
}
