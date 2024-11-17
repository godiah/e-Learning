<?php

namespace App\Policies;

use App\Models\CourseDiscount;
use App\Models\Courses;
use App\Models\User;

class CourseDiscountPolicy
{
    /**
     * Determine if the user can create a discount for the course.
     */
    public function create(User $user, Courses $course): bool
    {
        return $user->id === $course->instructor_id  || $user->isAdmin();
    }

    /**
     * Determine if the user can update a discount.
     */
    public function update(User $user, CourseDiscount $courseDiscount): bool
    {
        $course = $courseDiscount->course;

        return $user->id === $course->instructor_id  || $user->isAdmin() ||  $user->isContentAdmin();
    }

    /**
     * Determine if the user can delete a discount.
     */
    public function delete(User $user, CourseDiscount $courseDiscount): bool
    {
        $course = $courseDiscount->course;

        return $user->id === $course->instructor_id  || $user->isAdmin() ||  $user->isContentAdmin();
    }
}
