<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function delete(User $user, Enrollment $enrollment)
    {
        return $user->id === $enrollment->user_id;
    }

    public function update(User $user, Enrollment $enrollment)
    {
        return $user->id === $enrollment->course->instructor_id;
    }
}
