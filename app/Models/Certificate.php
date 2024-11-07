<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'course_id',
        'user_id',
        'title',
        'description',
        'completion_date',
        'linkedin_url'
    ];

    protected $casts = [
        'completion_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Courses::class);
    }
}
