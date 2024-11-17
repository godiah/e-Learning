<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lessons extends Model
{
    use HasFactory;

    protected $fillable = ['course_id','instructor_id','title', 'content', 'total_watch_time','order_index'];

    public function subcontents()
    {
        return $this->hasMany(LessonSubcontent::class,'lesson_id')->orderBy('order_index');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class,'lesson_id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class,'lesson_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class,'lesson_id');
    }

    protected static function booted()
    {
        static::created(function ($lesson) {
            $lesson->course->updateVideoLength();
            $lesson->course->calculateTotalLessons();
            $lesson->course->calculateTotalContent();
        });

        static::updated(function ($lesson) {
            $lesson->course->updateVideoLength();
            $lesson->course->calculateTotalLessons();
            $lesson->course->calculateTotalContent();
        });

        static::deleted(function ($lesson) {
            $lesson->course->updateVideoLength();
            $lesson->course->calculateTotalLessons();
            $lesson->course->calculateTotalContent();
        });
    }
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($lesson) {
            // Get the highest order_index for the course
            $maxOrder = static::where('course_id', $lesson->course_id)
                ->max('order_index');
            
            // Set the new order_index
            $lesson->order_index = $maxOrder ? $maxOrder + 1 : 1;
        });
    }
}
