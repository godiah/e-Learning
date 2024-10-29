<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courses extends Model
{
    use HasFactory;

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
        'video_length_minutes'
    ];

    protected $casts = [
        'objectives' => 'array',
        'who_is_for' => 'array',
        'last_updated' => 'datetime',
    ];

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

    public function getVideoLengthAttribute()
    {
        return $this->lessons()->sum('video_duration');
    }

    public function updateVideoLength()
    {
        $totalMinutes = $this->lessons()->sum('video_duration');
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        $this->video_length = $totalMinutes;
        $this->video_length_hours = $hours;
        $this->video_length_minutes = $minutes;
        $this->save();
    }

    public function getFormattedVideoLengthAttribute()
    {
        if ($this->video_length_hours > 0) {
            return "{$this->video_length_hours}h {$this->video_length_minutes}m";
        } else {
            return "{$this->video_length_minutes}m";
        }
    }

    public function getTotalLessonsAttribute()
    {
        return $this->lessons()->count();
    }

    public function getTotalQuizzesAttribute()
    {
        return $this->quizzes()->count();
    }

    public function getTotalAssignmentsAttribute()
    {
        return $this->assignments()->count();
    }

    public function getTotalContentAttribute()
    {
        return $this->totalLessons + $this->totalQuizzes + $this->totalAssignments;
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
        return $this->hasMany(Discussion::class);
    }

    
}
