<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResponseResource extends JsonResource
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
            'question_id' => $this->quiz_question_id,
            'selected_answer_id' => $this->selected_answer_id,
            'is_correct' => $this->is_correct,
            'score' => $this->score,
        ];
    }
}
