<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcontentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'subcontent_id',
        'watch_time',
        'is_completed',
        'last_position',
        'completed_at'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_position' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subcontent()
    {
        return $this->belongsTo(LessonSubcontent::class, 'subcontent_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lessons::class, 'lesson_id');
    }
}
