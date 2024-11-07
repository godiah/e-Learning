<?php

namespace App\Enums;

enum LessonProgressStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public static function fromWatchPercentage(float $watchedPercentage): self
    {
        return match(true) {
            $watchedPercentage >= 90 => self::COMPLETED,
            $watchedPercentage > 0 => self::IN_PROGRESS,
            default => self::NOT_STARTED
        };
    }
}
