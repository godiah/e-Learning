<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'profile_pic_url' => $this->profile_pic_url,
            'is_instructor' => $this->isInstructor(),
            'is_admin' => $this->isAdmin(),
            'is_student' => $this->isStudent(),
            'is_affiliate' => $this->isAffiliate(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->roles->pluck('name'),
            'courses_count' => $this->courses()->count(),
            'enrollments_count' => $this->enrollments()->count(),
            // Add other counts or relationships as needed
        ];
    }
}
