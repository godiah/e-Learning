<?php

namespace App\Models;

use App\Helpers\CacheHelper;
use App\Traits\HasCourseStatistics;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Courses extends Model
{
    use HasFactory, HasCourseStatistics;

    protected $fillable = [
        'instructor_id', 
        'category_id', 
        'title', 
        'description', 
        'detailed_description',
        'course_image',
        'price', 
        'level',
        'language',
        'objectives',
        'num_resources',
        'requirements',
        'who_is_for',
        'video_length',
        'video_length_hours',
        'video_length_minutes',
        'total_lessons', 'total_quizzes', 'total_assignments', 'total_content', 'duration', 'pass_mark'
    ];

    protected $casts = [
        'objectives' => 'array',
        'who_is_for' => 'array',
        'last_updated' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($course) {
            CacheHelper::clearInstructorCaches($course->instructor_id);
        });

        static::updated(function ($course) {
            CacheHelper::clearCourseCaches($course->id);
            CacheHelper::clearInstructorCaches($course->instructor_id);
            
            if ($course->wasChanged('instructor_id')) {
                CacheHelper::clearInstructorCaches($course->getOriginal('instructor_id'));
            }
        });

        static::deleted(function ($course) {
            CacheHelper::clearCourseCaches($course->id);
            CacheHelper::clearInstructorCaches($course->instructor_id);
        });
    }

    /**
     * Clear all instructor-related caches
     */
    // private static function clearInstructorCaches($instructorId)
    // {
    //     Cache::forget("instructor:{$instructorId}:avg_rating");
    //     Cache::forget("instructor:{$instructorId}:total_reviews");
    //     Cache::forget("instructor:{$instructorId}:total_enrollments");
    //     Cache::forget("instructor:{$instructorId}:total_courses");
    // }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lessons::class, 'course_id');
    }

    public function quizzes()
    {
        return $this->hasManyThrough(Quiz::class, Lessons::class, 'course_id', 'lesson_id');
    }

    public function assignments()
    {
        return $this->hasManyThrough(Assignment::class, Lessons::class, 'course_id', 'lesson_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }   

    public function reviews()
    {
        return $this->hasMany(Review::class, 'course_id');
    }

    public function discussion()
    {
        return $this->hasMany(Discussion::class, 'course_id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function discounts()
    {
        return $this->hasMany(CourseDiscount::class,'course_id');
    }

    public function activeDiscount()
    {
        $now = now()->startOfDay();
    
        return $this->discounts()
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->latest()
            ->first();
    }
    public function validDiscount()
{
    return $this->hasOne(CourseDiscount::class, 'course_id');

}

    public function carts()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }    
}
