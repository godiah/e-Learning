<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson' => new LessonsResource($this->lesson),
            'total_watch_time' => $this->time_watched,
            'status' => $this->status,
            'completed_subcontents' => $this->completed_subcontents,
            'quiz_completed' => $this->quiz_completed,
            'last_watched_at' => $this->last_watched_at,
            'subcontents_progress' => SubcontentProgressResource::collection(
                $this->lesson->subcontents->map(function ($subcontent) {
                    return [
                        'subcontent' => $subcontent,
                        'progress' => $subcontent->progress()->where('user_id', auth()->id())->first()
                    ];
                })
            )
        ];
    }
}
