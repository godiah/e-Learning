<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\AdminCreationPolicy;
use App\Models\Courses;
use App\Models\Lessons;
use App\Models\Quiz;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Policies\QuizPolicy;
use App\Models\AssignmentSubmission;
use App\Models\CourseDiscount;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Enrollment;
use App\Models\Review;
use App\Policies\AssignmentPolicy;
use App\Policies\CourseDiscountPolicy;
use App\Policies\DiscussionPolicy;
use App\Policies\DiscussionReplyPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\ReviewPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */

    protected $policies = [
        User::class => AdminCreationPolicy::class,
        Courses::class => CoursePolicy::class,
        Lessons::class => LessonPolicy::class,
        Quiz::class => QuizPolicy::class,
        AssignmentSubmission::class => AssignmentPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        Review::class => ReviewPolicy::class,
        Discussion::class => DiscussionPolicy::class,
        DiscussionReply::class => DiscussionReplyPolicy::class,
        CourseDiscount::class => CourseDiscountPolicy::class
    ];

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
