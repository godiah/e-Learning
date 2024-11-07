<?php

namespace App\Observers;

use App\Models\Discussion;

class DiscussionObserver
{
    /**
     * Handle the Discussion "created" event.
     */
    public function created(Discussion $discussion): void
    {
        // $discussion->user_id = auth()->id();
    }

    /**
     * Handle the Discussion "updated" event.
     */
    public function updated(Discussion $discussion): void
    {
        //
    }

    /**
     * Handle the Discussion "deleted" event.
     */
    public function deleted(Discussion $discussion): void
    {
        //
    }

    /**
     * Handle the Discussion "restored" event.
     */
    public function restored(Discussion $discussion): void
    {
        //
    }

    /**
     * Handle the Discussion "force deleted" event.
     */
    public function forceDeleted(Discussion $discussion): void
    {
        //
    }
}
