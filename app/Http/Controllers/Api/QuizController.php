<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Lessons;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    //Display  quizzes for a lesson
    public function index(Lessons $lesson)
    {
        $quiz = $lesson->quizzes()
                        ->with(['questions'])
                        ->get();
        if($quiz->count() > 0)
        {
            return QuizResource::collection($quiz);
        }
        else
        {
            return response()->json(['message' => 'No available quizzes found'], 404);
        }
    }

    //Display a single quiz
    public function show(Lessons $lesson, Quiz $quiz) 
    {
        if ($quiz->lesson_id !== $lesson->id) 
        {
            return response()->json(['error' => 'Quiz not found in this lesson'], 404);
        }

        $quiz->load(['questions']);
        
        return new QuizResource($quiz);
    }

    //Add a quiz/Store
    public function store(Request $request, Lessons $lesson)
    {
        $user = request()->user();
    
        if (!$user->is_instructor) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $validator = Validator::make($request->all(),[  
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('quizzes')->where(function ($query) use ($lesson) {
                    return $query->where('lesson_id', $lesson->id);
                }),
            ],
            'time_limit' => 'required|integer|min:1',
            'max_attempts' => 'required|integer|min:1',
            'instructions' => 'nullable|string'                 
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }


        $quiz = $lesson->quizzes()->create([
            'title' => $request->title,
            'time_limit' => $request->time_limit,
            'max_attempts' => $request->max_attempts,
            'instructions' => $request->instructions,
            'instructor_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Quiz created successfully',
            'data' => new QuizResource($quiz)
        ]);
    }
        
    //Update a quiz
    public function update(Request $request,Lessons $lesson, Quiz $quiz)
    {
        $user = request()->user();
    
        if (!$user->is_instructor) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        // Check if the authenticated instructor is the owner of the quiz
        if ($quiz->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($quiz->lesson_id !== $lesson->id) 
        {
            return response()->json(['error' => 'Quiz not found in this lesson'], 404);
        }

        $validator = Validator::make($request->all(),[ 
            'title' => [
                'string',
                'sometimes',
                'max:255',
                Rule::unique('quizzes')->where(function ($query) use ($lesson, $quiz) {
                    return $query->where('lesson_id', $lesson->id)
                                ->where('id', '!=', $quiz->id);
                }),
            ],
            'time_limit' => 'sometimes|integer|min:1',
            'max_attempts' => 'sometimes|integer|min:1',
            'instructions' => 'sometimes|nullable|string'                 
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $dataToUpdate = [];

        if ($request->has('title')) {
            $dataToUpdate['title'] = $request->title;
        }

        if ($request->has('time_limit')) {
            $dataToUpdate['time_limit'] = $request->time_limit;
        }

        if ($request->has('max_attempts')) {
            $dataToUpdate['max_attempts'] = $request->max_attempts;
        }

        if ($request->has('instructions')) {
            $dataToUpdate['instructions'] = $request->instructions;
        }

        $quiz->update($dataToUpdate);

        return response()->json([
            'message' => 'Quiz updated successfully',
            'data' => new QuizResource($quiz)
        ]);
    }

    //Delete a quiz
    public function destroy(Lessons $lesson,Quiz $quiz)
    {
        $user = request()->user();
    
        if (!$user->is_instructor) {
            return response()->json([
                'message' => 'Unauthorized access. Only instructors can delete quizzes.',
            ], 403);
        }

        // Check if the authenticated instructor is the owner of the quiz
        if ($quiz->instructor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized access. You can only delete your own quizzes.',
            ], 403);
        }

        if ($quiz->lesson_id !== $lesson->id) 
        {
            return response()->json(['error' => 'Quiz not found in this lesson'], 404);
        }
        $quiz->delete();
        return response()->json([
            'message' => 'Quiz deleted successfully'
        ]);
    }
}
