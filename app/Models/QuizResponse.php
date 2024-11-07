<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'user_id',
        'quiz_question_id',
        'selected_answer_id',
        'score',
        'is_correct'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'score' => 'float'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function quiz() {
        return $this->belongsTo(Quiz::class);
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function selectedAnswer()
    {
        return $this->belongsTo(QuizAnswer::class, 'selected_answer_id');
    }
}
