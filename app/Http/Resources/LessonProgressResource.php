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
            'lesson_id' => $this->lesson_id,
            'time_watched' => $this->time_watched,
            'status' => $this->status->value,
            'last_watched_at' => $this->last_watched_at,
            //'can_proceed' => $this->can_proceed
        ];
    }
}
