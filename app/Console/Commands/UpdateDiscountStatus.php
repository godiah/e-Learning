<?php

namespace App\Console\Commands;

use App\Models\CourseDiscount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDiscountStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discounts:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of course discounts based on dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now()->startOfDay();

        // Activate discounts that should start today
        $activated = CourseDiscount::query()
            ->where('is_active', false)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->update(['is_active' => true]);

        // Deactivate expired discounts
        $deactivated = CourseDiscount::query()
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->where('end_date', '<', $now);
            })
            ->update(['is_active' => false]);

        $this->info("Activated {$activated} discounts and deactivated {$deactivated} discounts.");
    }
}
