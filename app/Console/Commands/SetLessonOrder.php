<?php

namespace App\Console\Commands;

use App\Models\Courses;
use Illuminate\Console\Command;

class SetLessonOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lessons:set-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set order index for existing lessons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courses = Courses::with('lessons')->get();

        foreach ($courses as $course) {
            // Get lessons ordered by created_at to maintain chronological order
            $lessons = $course->lessons()->orderBy('created_at')->get();
            
            foreach ($lessons as $index => $lesson) {
                $lesson->update([
                    'order_index' => $index + 1
                ]);
            }
        }

        $this->info('Lesson order indices have been set successfully!');
    }
}
