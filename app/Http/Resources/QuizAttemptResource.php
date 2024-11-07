<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
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
            'quiz_id' => $this->quiz_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'score' => $this->score,
            'status' => $this->status,
            'remaining_time' => $this->remaining_time, // Using the dynamic accessor
            'attempt_number' => $this->attempt_number,
            'responses' => QuizResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}
