<?php

namespace App\Models;

use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'quiz_id','attempt_number','start_time','end_time', 'device_id','score','status','progress_status'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'score' => 'float'
    ];

    protected static function booted()
    {
        static::created(function ($model) {
            $courseId = $model->quiz->lesson->course_id;
            app(CourseProgressService::class)->refreshProgress($courseId, $model->user_id);
        });

        static::updated(function ($model) {
            $courseId = $model->quiz->lesson->course_id;
            app(CourseProgressService::class)->refreshProgress($courseId, $model->user_id);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function responses()
    {
        return $this->hasMany(QuizResponse::class);
    }

    public function getRemainingTimeAttribute()
    {
        $now = now();
    
        if ($now->greaterThan($this->end_time)) {
            return '0 minutes';
        }

        $interval = $now->diff($this->end_time);
        $hours = $interval->h;
        $minutes = $interval->i;

        $timeParts = [];
        if ($hours > 0) {
            $timeParts[] = "{$hours} hour" . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0) {
            $timeParts[] = "{$minutes} minute" . ($minutes > 1 ? 's' : '');
        }

        return implode(' ', $timeParts);
    }

    public function updateQuietly(array $attributes = [], array $options = [])
    {
        return static::withoutTimestamps(function () use ($attributes, $options) {
            return $this->update($attributes, $options);
        });
    }
}
