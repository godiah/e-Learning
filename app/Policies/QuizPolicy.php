<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Determine if the user can create/edit/delete quiz content.
     */
    public function manage(User $user, Quiz $quiz)
    {
        return $user->id === $quiz->instructor_id;
    }
}
