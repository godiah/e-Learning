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
use App\Policies\AssignmentPolicy;

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
