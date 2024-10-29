<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AssignmentSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    /**
     * Create a new policy instance.
     */
    use HandlesAuthorization;

    public function grade(User $user, AssignmentSubmission $submission)
    {
        return $user->id === $submission->assignment->instructor_id;
    }
}
