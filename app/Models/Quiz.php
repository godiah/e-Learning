<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'instructor_id', 'title'];

    public function lesson()
    {
        return $this->belongsTo(Lessons::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function userattempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
