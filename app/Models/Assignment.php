<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'title', 'instructor_id', 'description'];

    public function lesson()
    {
        return $this->belongsTo(Lessons::class);
    }

    public function assignmentsubmission()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
