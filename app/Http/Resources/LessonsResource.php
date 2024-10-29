<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'instructor_id' => $this->instructor_id,
            'title' => $this->title,
            'content' => $this->content,
            'video_url' => $this->video_url ? Storage::url($this->video_url) : null,
            'video_duration' => $this->video_duration,
            'order_index' => $this->order_index,
            'resource_path' => $this->resource_path ? Storage::url($this->resource_path) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
