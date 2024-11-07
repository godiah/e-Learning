<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseDiscountResource;
use App\Models\CourseDiscount;
use App\Models\Courses;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseDiscountController extends Controller
{
    use AuthorizesRequests;

    // View Course Discounts
    public function index(Courses $course)
    {
        $discounts = $course->discounts()
            ->with(['course' => function ($query) {
                $query->select('id', 'title', 'price', 'instructor_id');
            }])
            ->latest()
            ->paginate(10);

        if ($discounts->isEmpty()) {
            return response()->json([
                'message' => 'No discounts found for this course.',
            ], 404);
        }

        return CourseDiscountResource::collection($discounts);
    }

    // View single course discounts
    public function show(CourseDiscount $discount)
    {
        $discount->load(['course' => function ($query) {
            $query->select('id', 'title', 'instructor_id');
        }]);

        return new CourseDiscountResource($discount);
    }

    // Add a new course discount
    public function store(Request $request, Courses $course)
    {
        try {
            $this->authorize('create', [CourseDiscount::class, $course]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'discount_rate' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        // Check for existing active discount
        if ($course->activeDiscount()) {
            throw ValidationException::withMessages([
                'course' => ['This course already has an active discount.']
            ]);
        }

        // Check for date conflicts with other discounts
        $hasConflict = $course->discounts()
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'dates' => ['The selected dates conflict with an existing discount period for the given course.']
            ]);
        }

        $now = Carbon::now()->startOfDay();
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        
        // Set is_active based on whether start_date is today
        $validated['is_active'] = $startDate->equalTo($now);

        $discount = $course->discounts()->create([
            'discount_rate' => $validated['discount_rate'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $validated['is_active'],
        ]);

        return new CourseDiscountResource($discount);
    }

    // Update existing discounts
    public function update(Request $request, CourseDiscount $discount)
    {
        try {
            $this->authorize('update',$discount);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized Action.'], 403);
        }

        $validated = $request->validate([
            'discount_rate' => 'sometimes|required|numeric|min:0|max:100',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
        ]);

        // Check for date conflicts excluding current discount
        $hasConflict = $discount->course->discounts()
            ->where('id', '!=', $discount->id)
            ->where(function ($query) use ($validated, $discount) {
                $startDate = $validated['start_date'] ?? $discount->start_date;
                $endDate = $validated['end_date'] ?? $discount->end_date;
                
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'dates' => ['The selected dates conflict with an existing discount period.']
            ]);
        }

        // Update is_active status if dates are being modified
        if (isset($validated['start_date'])) {
            $now = now()->startOfDay();
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $validated['is_active'] = $now->equalTo($startDate);
        }

        $discount->update($validated);

        return new CourseDiscountResource($discount);
    }

    // Delete exisiting discounts
    public function destroy(CourseDiscount $discount)
    {
        $discount->delete();
        return response()->json([
            'message' => 'Discount deleted successfully.'
        ]);
    }
}
