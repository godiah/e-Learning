<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionReply extends Model
{
    use HasFactory;

    protected $fillable = ['discussion_id', 'user_id', 'content'];

    protected $with = ['user']; // Always load user relationship

    protected static function boot()
    {
        parent::boot();
        
        // Auto-set user_id when creating
        static::creating(function ($reply) {
            $user = request()->user();
            $reply->user_id = $user->id;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }
}
