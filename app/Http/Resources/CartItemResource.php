<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => [
                'id' => $this->course->id,
                'instructor_id' => $this->course->instructor_id,
                'instructor' => [
                    'name' => $this->course->instructor->name,
                ],
                'title' => $this->course->title,
                'price' => $this->course->price,
                'level' => $this->course->level,
            ],
            'price' => $this->price,
            'discount_amount' => $this->discount_amount,
            'final_price' => $this->final_price,
        ];
    }
}
