<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQuizAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
        // return [
        //     'id' => $this->id,
        //     'user_id' => $this->user_id,
        //     'quiz_id' => $this->quiz_id,            
        //     'question_id' => $this->question_id,            
        //     'selected_answer_id' => $this->selected_answer_id,                            
        //     'created_at' => $this->created_at,
        // ];
    }
}
