<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'user_id', 'quiz_average', 'assignment_average', 'total_grade', 'completed_items_count', 'total_items_count', 'status'
    ];

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
