<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    //Display all quizzes
    public function index()
    {
        $quiz = Quiz::get();
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
    public function show(Quiz $quiz) 
    {
        return new QuizResource($quiz);
    }

    //Add a quiz/Store
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'lesson_id' => 'required|exists:lessons,id',  
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('quizzes')->where(function ($query) use ($request) {
                    return $query->where('lesson_id', $request->lesson_id);
                }),
            ],                 
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $quiz = Quiz::create([
            'lesson_id' => $request->lesson_id,
            'title' => $request->title,
        ]);

        return response()->json([
            'message' => 'Quiz created successfully',
            'data' => new QuizResource($quiz)
        ]);
    }
        
    //Update a quiz
    public function update(Request $request, Quiz $quiz)
    {
        $validator = Validator::make($request->all(),[
            'lesson_id' => 'exists:lessons,id',  
            'title' => [
                'string',
                'max:255',
                Rule::unique('quizzes')->where(function ($query) use ($request, $quiz) {
                    return $query->where('lesson_id', $request->lesson_id ?? $quiz->lesson_id)
                                 ->where('id', '!=', $quiz->id);
                }),
            ],                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $quiz ->update([
            'lesson_id' => $request->lesson_id,
            'title' => $request->title,
        ]);

        return response()->json([
            'message' => 'Quiz updated successfully',
            'data' => new QuizResource($quiz)
        ]);
    }

    //Delete a quiz
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();
        return response()->json([
            'message' => 'Quiz deleted successfully'
        ]);
    }
}
