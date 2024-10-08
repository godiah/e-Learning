<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'title'];

    public function lesson()
    {
        return $this->belongsTo(Lessons::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }
}
