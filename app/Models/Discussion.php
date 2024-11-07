<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'user_id', 'title', 'content'];

    protected $with = ['user']; // Always load user relationship

    protected static function boot()
    {
        parent::boot();
        
        // Auto-set user_id when creating
        static::creating(function ($discussion) {
            $user = request()->user();
            $discussion->user_id = $user->id;
            //$discussion->user_id = auth()->user()?->id;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Courses::class);
    }

    public function replies()
    {
        return $this->hasMany(DiscussionReply::class);
    }
}
