<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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
            'instructor_id' => $this->instructor_id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,            
            'created_at' => $this->created_at,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
