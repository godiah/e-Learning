<?php

namespace App\Models;

use App\Enums\LessonProgressStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'time_watched',
        'status',
        'last_watched_at', 'completed_at','completed_subcontents'
    ];

    protected $casts = [
        'status' => LessonProgressStatus::class,
        'last_watched_at' => 'datetime',
        'completed_subcontents' => 'array',
        'completed_at' => 'datetime'
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

}
