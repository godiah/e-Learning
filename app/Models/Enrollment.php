<?php

namespace App\Models;

use App\Helpers\CacheHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'course_id', 'enrollment_date', 'completion_date'
    ];

    protected static function booted()
    {
        static::created(function ($enrollment) {
            CacheHelper::clearEnrollmentRelatedCaches($enrollment);
        });

        static::deleted(function ($enrollment) {
            CacheHelper::clearEnrollmentRelatedCaches($enrollment);
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
