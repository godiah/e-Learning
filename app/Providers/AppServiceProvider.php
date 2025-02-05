<?php

namespace App\Providers;

use App\Models\CourseDiscount;
use App\Models\Lessons;
use App\Models\User;
use App\Observers\CourseDiscountObserver;
use App\Observers\LessonObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force laravel to allow https for purposes of hosting
        // if (env('APP_ENV') == 'production') {
        //     $this->app['request']->server->set('HTTPS', true);
        // }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        User::observe(UserObserver::class);

        Lessons::observe(LessonObserver::class);

        CourseDiscount::observe(CourseDiscountObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\UpdateDiscountStatus::class,
                \App\Console\Commands\SetLessonOrder::class,
            ]);
        }
    }
}
