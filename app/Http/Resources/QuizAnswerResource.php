<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAnswerResource extends JsonResource
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
            'question_id' => $this->question_id,
            'answer' => $this->answer,            
            'is_correct' => $this->is_correct,            
            'created_at' => $this->created_at,
        ];
    }
}
