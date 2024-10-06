<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonsResource extends JsonResource
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
            'course_id' => $this->course_id,
            'title' => $this->title,
            'content' => $this->content,
            'video_url' => $this->video_url,            
            'order_index' => $this->order_index,            
            'created_at' => $this->created_at,
        ];
    }
}
