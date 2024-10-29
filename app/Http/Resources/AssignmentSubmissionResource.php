<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'assignment_id' => $this->assignment_id, 
            'user_id' => $this->user_id, 
            'user' => [
                'name' => $this->user->name,                
            ],
            'submission_text' => $this->submission_text, 
            'submission_file_path' => $this->submission_file_path,
            'submission_date' => $this->submission_date, 
            'grade' => $this->grade, 
            'feedback' => $this->feedback,
        ];
    }
}
