<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Courses;
use App\Models\Review;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    public function index(Courses $course)
    {
        $reviews = $course->reviews()
            ->with(['user:id,name'])
            ->latest()
            ->paginate(10);

        if ($reviews->isEmpty()) {
            return response()->json([
                'message' => 'No reviews found for this course.'
            ]);
        }
            
        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, Courses $course)
    {
        $user = request()->user();

        if (!$course->enrollments()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You must be enrolled in the course to review it'
            ], 403);
        }

        // Check if user has already reviewed this course
        $existingReview = $course->reviews()
            ->where('user_id', $user->id)
            ->exists();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this course'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $review = $course->reviews()->create([
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update course average rating
        //$course->updateAverageRating();

        return response()->json([
            'message' => 'Review posted successfully',
            'data' => new ReviewResource($review->load('user'))
        ], 201);
    }

    public function update(Request $request, Review $review)
    {
        try {
            $this->authorize('update', $review);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:0|max:5',
            'comment' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->messages(),
            ], 422);
        }

        $dataToUpdate = [];

        if ($request->has('rating')) {
            $dataToUpdate['rating'] = $request->rating;
        }

        if ($request->has('comment')) {
            $dataToUpdate['comment'] = $request->comment;
        }

        if (empty($dataToUpdate)) {
            return response()->json(['message' => 'No data to update'], 400);
        }

        $review->update($dataToUpdate);

        // Update course average rating
        //$review->course->updateAverageRating();

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => new ReviewResource($review->fresh()->load('user'))
        ]);
    }

    public function destroy(Review $review)
    {
        try {
            $this->authorize('delete', $review);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized action'], 403);
        }
        $course = $review->course;
        $review->delete();
        
        // Update course average rating
        //$course->updateAverageRating();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
