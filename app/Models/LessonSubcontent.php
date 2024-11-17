<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSubcontent extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id','name', 'description', 'video_url', 'order_index', 'video_duration','resource_path'];

    public function lesson()
    {
        return $this->belongsTo(Lessons::class, 'lesson_id');
    }

    protected static function boot()
    {
        parent::boot();

        // After a subcontent is deleted, reorder the remaining ones
        static::deleted(function ($subcontent) {
            $lesson = $subcontent->lesson;
            
            // Get all remaining subcontents for this lesson
            $remainingSubcontents = $lesson->subcontents()
                ->where('order_index', '>', $subcontent->order_index)
                ->orderBy('order_index')
                ->get();

            // Reorder the remaining subcontents
            foreach ($remainingSubcontents as $remaining) {
                $remaining->update([
                    'order_index' => $remaining->order_index - 1
                ]);
            }
        });

        // Before creating, set the order_index
        static::creating(function ($subcontent) {
            if (!$subcontent->order_index) {
                $maxOrder = static::where('lesson_id', $subcontent->lesson_id)
                    ->max('order_index');
                $subcontent->order_index = $maxOrder ? $maxOrder + 1 : 1;
            }
        });
    }
}
