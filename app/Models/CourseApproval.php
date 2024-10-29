<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'price', 'level', 'category_id', 'instructor_id', 'status'
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }
}
