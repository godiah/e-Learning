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
        $instructor = $this->instructor;
        $course = $this->course;

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $course->title,
            'instructor_id' => $this->instructor_id,
            'instructor' => $instructor->name,
            'title' => $this->title,
            'content' => $this->content,
            'total_watch_time' => $this->total_watch_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subcontents' => $this->whenLoaded('subcontents', function () {
                return $this->subcontents->map(function ($subcontent) {
                    return [
                        'id' => $subcontent->id,
                        'lesson_id' => $subcontent->lesson_id,
                        'name' => $subcontent->name,
                        'content' => $subcontent->description,
                        'video_url' => $subcontent->video_url ? Storage::url($subcontent->video_url) : null,
                        'order_index' => $subcontent->order_index,
                        'video_duration' => $subcontent->video_duration,
                        'resource_path' => $subcontent->resource_path ? Storage::url($subcontent->resource_path) : null,
                        'created_at' => $subcontent->created_at,
                        'updated_at' => $subcontent->updated_at,
                    ];
                })->sortBy('order_index')->values();
            }),
        ];
    }
}
