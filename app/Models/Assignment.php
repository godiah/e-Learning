<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'title', 'instructor_id', 'description'];

    public function course()
    {
        return $this->lesson->course();
    }

    public function lesson()
    {
        return $this->belongsTo(Lessons::class);
    }

    public function assignmentsubmission()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    protected static function booted()
    {
        static::created(function ($assignment) {
            $assignment->course->calculateTotalAssignments();
            $assignment->course->calculateTotalContent();
        });
    
        static::deleted(function ($assignment) {
            $assignment->course->calculateTotalAssignments();
            $assignment->course->calculateTotalContent();
        });
    }
}
