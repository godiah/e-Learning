<?php

namespace App\Observers;

use App\Models\Lessons;

class LessonObserver
{
    public function created(Lessons $lesson)
    {
        $lesson->course->updateVideoLength();
    }

    public function updated(Lessons $lesson)
    {
        $lesson->course->updateVideoLength();
    }

    public function deleted(Lessons $lesson)
    {
        $lesson->course->updateVideoLength();
    }
}
