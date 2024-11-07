<?php

namespace App\Policies;

use App\Models\Courses;
use App\Models\Discussion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DiscussionPolicy
{
    public function viewAny(User $user, Courses $course): bool
    {
        return $user->enrollments()->where('course_id', $course->id)->exists();
    }

    public function create(User $user, Courses $course): bool
    {
        return $user->enrollments()->where('course_id', $course->id)->exists();
    }

    public function update(User $user, Discussion $discussion): bool
    {
        return $user->id === $discussion->user_id;
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        return $user->id === $discussion->user_id;
    }
}
