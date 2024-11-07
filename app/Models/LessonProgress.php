<?php

namespace App\Models;

use App\Enums\LessonProgressStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'time_watched',
        'status',
        'last_watched_at'
    ];

    protected $casts = [
        'status' => LessonProgressStatus::class,
        'last_watched_at' => 'datetime'
    ];

    /**
     * last_watched_at is used to:
     * 1. Track user engagement and learning patterns
     * 2. Implement "resume where you left off" functionality
     * 3. Generate activity reports (e.g., "Last active 2 hours ago")
     * 4. Detect inactive/stale learning sessions
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lessons::class);
    }

    public function updateProgress(int $timeWatched, Lessons $lesson)
    {
        $this->time_watched = $timeWatched;
        $this->last_watched_at = now();
        
        // Calculate watch percentage and update status
        $watchPercentage = ($timeWatched / $lesson->video_duration) * 100;
        $this->status = LessonProgressStatus::fromWatchPercentage($watchPercentage);
        
        return $this->save();
    }

    public function isCompleted(): bool
    {
        return $this->status === LessonProgressStatus::COMPLETED;
    }
}
