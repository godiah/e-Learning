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
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'quiz_id' => $this->quiz_id,            
            'attempt_number' => $this->attempt_number,            
            'start_time' => $this->start_time,            
            'end_time' => $this->end_time,           
            'score' => $this->score,           
            'created_at' => $this->created_at,
        ];
    }
}
