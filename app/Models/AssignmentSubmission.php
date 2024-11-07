<?php

namespace App\Models;

use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['assignment_id', 'user_id', 'submission_text', 'submission_file_path','submission_date', 'grade', 'feedback','is_resubmission_allowed', 
        'resubmission_count','progress_status'];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
    
        static::creating(function ($submission) {
            $submission->submission_date = now();
        });
    
        static::updating(function ($submission) {
            $submission->submission_date = now();
        });

        static::created(function ($model) {
            $courseId = $model->assignment->lesson->course_id;
            app(CourseProgressService::class)->refreshProgress($courseId, $model->user_id);
        });
    
        static::updated(function ($model) {
            $courseId = $model->assignment->lesson->course_id;
            app(CourseProgressService::class)->refreshProgress($courseId, $model->user_id);
        });
    }

}
