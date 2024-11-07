<?php

namespace App\Policies;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;

class DiscussionReplyPolicy
{
    public function create(User $user, Discussion $discussion): bool
    {
         return $user->enrollments()->where('course_id', $discussion->course_id)->exists();
    }

    public function update(User $user, DiscussionReply $discussionReply): bool
    {
        return $user->id === $discussionReply->user_id;
    }

    public function delete(User $user, DiscussionReply $discussionReply): bool
    {
        return $user->id === $discussionReply->user_id;
    }
}
