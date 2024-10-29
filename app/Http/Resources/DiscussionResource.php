<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionResource extends JsonResource
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
            'user' => [
                'name' => $this->user->name,
                'profile_pic_url' => $this->user->profile_pic_url,
            ],
            'course_id' => $this->course_id,
            'title' => $this->title,
            'content' => $this->content,
            'created_at' => $this->created_at,
        ];
    }
}
