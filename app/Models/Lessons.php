<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lessons extends Model
{
    use HasFactory;

    protected $fillable = ['course_id','instructor_id','title', 'content', 'video_url', 'order_index', 'video_duration','resource_path'];


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
        });

        static::deleted(function ($lesson) {
            $lesson->course->updateVideoLength();
            $lesson->course->calculateTotalLessons();
            $lesson->course->calculateTotalContent();
        });
    }
}
