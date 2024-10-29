<?php

namespace App\Policies;

use App\Models\Courses;
use App\Models\Lessons;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;
    /**
     * Create a new policy instance.
     */
    public function update(User $user, Lessons $lesson, Courses $course)
    {
        return $user->id === $lesson->instructor_id && $lesson->course_id === $course->id;
    }

    public function delete(User $user, Lessons $lesson, Courses $course)
    {
        return $user->id === $lesson->instructor_id && $lesson->course_id === $course->id;
    }
}
