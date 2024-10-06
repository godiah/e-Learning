<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizQuestionResource;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizQuestionController extends Controller
{
    //Display all questions
    public function index()
    {
        $question = QuizQuestion::get();
        if($question->count() > 0)
        {
            return QuizQuestionResource::collection($question);
        }
        else
        {
            return response()->json(['message' => 'No available questions found'], 404);
        }
    }

    //Display a single quiz
    public function show(QuizQuestion $question) 
    {
         $question->load('answers'); // Only load answers to avoid loading quiz recursively
        return new QuizQuestionResource($question);
    }

    //Add a question
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'quiz_id' => 'required|exists:quizzes,id',  
            'question' => [
                'required',
                'string',
                // Ensure the question is unique within the same quiz
                function ($attribute, $value, $fail) use ($request) {
                    $exists = QuizQuestion::where('quiz_id', $request->quiz_id)
                        ->where('question', $value)
                        ->exists();
                    if ($exists) {
                        $fail("The question already exists!");
                    }
                },
            ],                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $question = QuizQuestion::create([
            'quiz_id' => $request->quiz_id,
            'question' => $request->question,
        ]);

        return response()->json([
            'message' => 'Question created successfully',
            'data' => new QuizQuestionResource($question)
        ]);
    }

    //Update a question
    public function update(Request $request, QuizQuestion $question)
    {
        $validator = Validator::make($request->all(),[
            'quiz_id' => 'exists:quizzes,id',  
            'question' => [
                'string',
                // Ensure the question is unique within the same quiz if it is being updated
                function ($attribute, $value, $fail) use ($request, $question) {
                    if ($value && $request->quiz_id) {
                        $exists = QuizQuestion::where('quiz_id', $request->quiz_id)
                            ->where('question', $value)
                            ->where('id', '!=', $question->id) // Ignore current question
                            ->exists();
                        if ($exists) {
                            $fail("The question already exists!");
                        }
                    }
                },
            ],                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $question ->update([
            'quiz_id' => $request->quiz_id,
            'question' => $request->question,
        ]);

        return response()->json([
            'message' => 'Question updated successfully',
            'data' => new QuizQuestionResource($question)
        ]);

    }

    //Delete a question
    public function destroy(QuizQuestion $question)
    {
        $question->delete();
        return response()->json([
            'message' => 'Question deleted successfully'
        ]);
    }
}
