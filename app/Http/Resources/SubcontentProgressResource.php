<?php

namespace App\Http\Resources;

use App\Models\LessonSubcontent;
use App\Models\SubcontentProgress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcontentProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subcontent' => [
                'id' => $this['subcontent']->id,
                'name' => $this['subcontent']->name,
                'video_url' => $this['subcontent']->video_url,
                'video_duration' => $this['subcontent']->video_duration,
                'order_index' => $this['subcontent']->order_index,
            ],
            'progress' => $this['progress'] ? [
                'watch_time' => $this['progress']->watch_time,
                'last_position' => $this['progress']->last_position,
                'is_completed' => $this['progress']->is_completed,
                'completed_at' => $this['progress']->completed_at,
            ] : null,
            'is_locked' => $this->isLocked($this['subcontent']),
        ];
    }

    private function isLocked($subcontent)
    {
        if ($subcontent->order_index === 1) {
            return false;
        }

        $previousSubcontent = LessonSubcontent::where('lesson_id', $subcontent->lesson_id)
            ->where('order_index', $subcontent->order_index - 1)
            ->first();

        if (!$previousSubcontent) {
            return false;
        }

        $previousProgress = SubcontentProgress::where('user_id', auth()->id())
            ->where('subcontent_id', $previousSubcontent->id)
            ->first();

        return !$previousProgress || !$previousProgress->is_completed;
    }
}
