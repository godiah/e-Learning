<?php

namespace App\Policies;

use App\Models\Courses;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    // Allow anyone to view a course
    public function view(User $user, Courses $course)
    {
        return true;  // This will allow all users to view
    }

    // Determine if the given user can create a course
    public function create(User $user)
    {
        // Check if the user has the "instructor" role
        return $user->roles()->where('name', 'instructor')->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Courses $course)
    {
        // Allow only if the authenticated user is the instructor of the course
        return $user->id === $course->instructor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Courses $courses): bool
    {
        // Allow only if the authenticated user is the instructor of the course
        return $user->id === $courses->instructor_id || $user->isAdmin() ||  $user->isContentAdmin();
    }

    public function approveCoursesCommand(User $user)
    {
        return $user->isAdmin() ||  $user->isContentAdmin();
    }

}
