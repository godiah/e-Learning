<?php

namespace App\Observers;

use App\Models\CourseDiscount;
use Carbon\Carbon;

class CourseDiscountObserver
{
    public function saving(CourseDiscount $discount)
    {
        $now = Carbon::now()->startOfDay();
        $startDate = Carbon::parse($discount->start_date)->startOfDay();
        
        // If this is a new discount starting today
        if ($startDate->equalTo($now)) {
            $discount->is_active = true;
        }
        // If this is a future discount
        else if ($startDate->gt($now)) {
            $discount->is_active = false;
        }
        // If this is an existing discount that should be active
        else if ($now->between($startDate, Carbon::parse($discount->end_date)->endOfDay())) {
            $discount->is_active = true;
        }
        // Otherwise (past discount)
        else {
            $discount->is_active = false;
        }
    }

    /**
     * Handle the CourseDiscount "created" event.
     */
    public function created(CourseDiscount $courseDiscount): void
    {
        //
    }

    /**
     * Handle the CourseDiscount "updated" event.
     */
    public function updated(CourseDiscount $courseDiscount): void
    {
        //
    }

    /**
     * Handle the CourseDiscount "deleted" event.
     */
    public function deleted(CourseDiscount $courseDiscount): void
    {
        //
    }

    /**
     * Handle the CourseDiscount "restored" event.
     */
    public function restored(CourseDiscount $courseDiscount): void
    {
        //
    }

    /**
     * Handle the CourseDiscount "force deleted" event.
     */
    public function forceDeleted(CourseDiscount $courseDiscount): void
    {
        //
    }
}
