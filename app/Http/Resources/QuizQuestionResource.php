<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $quiz = $this->quiz;

        $data = [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question' => $this->question,
            'created_at' => $this->created_at,
        ];

        if ($user && ($user->id === $quiz->instructor_id)) {
            $data['answers'] = QuizAnswerResource::collection($this->whenLoaded('answers'));
        }

        return $data;
    }
}
