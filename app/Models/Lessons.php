<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lessons extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'content', 'video_url', 'order_index'];

    public function course()
    {
        return $this->belongsTo(Courses::class);
    }
}
