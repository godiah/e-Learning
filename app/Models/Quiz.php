<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id', 
        'instructor_id', 
        'title',
        'time_limit', // in minutes
        'max_attempts',        
        'instructions'
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'max_attempts' => 'integer'
    ];

    public function course()
    {
        return $this->lesson->course();
    }

    public function lesson()
    {
        return $this->belongsTo(Lessons::class, 'lesson_id');
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // Determine if quiz is available based on lesson completion
    public function isAvailableForUser(User $user)
    {
        $lessonCompleted = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $this->lesson_id)
            ->where('status', 'completed')
            ->exists();

        return $lessonCompleted;
    }

    protected static function booted()
    {
        static::created(function ($quiz) {
            $quiz->course->calculateTotalQuizzes();
            $quiz->course->calculateTotalContent();
        });
    
        static::deleted(function ($quiz) {
            $quiz->course->calculateTotalQuizzes();
            $quiz->course->calculateTotalContent();
        });
    }
}
