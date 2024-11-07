<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseDiscount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'discount_rate',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'discount_rate' => 'decimal:2'
    ];

    protected $attributes = [
        'is_active' => false,
    ];

    public function course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function isCurrentlyActive()
    {
        $currentDate = now();
        return $this->is_active && 
               (!$this->start_date || $this->start_date <= $currentDate) &&
               (!$this->end_date || $this->end_date >= $currentDate);
    }
}
