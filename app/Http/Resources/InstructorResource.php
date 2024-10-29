<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
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
                'email' => $this->user->email,
            ],
            'headline' => $this->headline,
            'bio' => $this->bio,
            'profile_pic_url' => $this->profile_pic_url,
            'terms_accepted' => $this->terms_accepted,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
