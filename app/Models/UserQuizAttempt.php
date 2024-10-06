<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'question_id',
        'selected_answer_id',
    ];

    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function quiz() {
        return $this->belongsTo(Quiz::class);
    }

    public function question() {
        return $this->belongsTo(QuizQuestion::class);
    }

    public function selectedAnswer() {
        return $this->belongsTo(QuizAnswer::class, 'selected_answer_id');
    }
}
