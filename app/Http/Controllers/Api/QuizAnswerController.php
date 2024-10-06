<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAnswerResource;
use App\Models\QuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizAnswerController extends Controller
{
    //Display all answers
    public function index()
    {
        $answer = QuizAnswer::get();
        if($answer->count() > 0)
        {
            return QuizAnswerResource::collection($answer);
        }
        else
        {
            return response()->json(['message' => 'No available answers found'], 404);
        }
    }

    //Display a single questioon
    public function show(QuizAnswer $answer) 
    {
        return new QuizAnswerResource($answer);
    }

    //Add an answer
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'question_id' => 'required|exists:quiz_questions,id',
            'answer' => 'required|string|max:255',
            'is_correct' => 'required|boolean',
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $answer = QuizAnswer::create([
            'question_id' => $request->question_id,
            'answer' => $request->answer,
            'is_correct' => $request->is_correct,
        ]);

        return response()->json([
            'message' => 'Answer created successfully',
            'data' => new QuizAnswerResource($answer)
        ]);
    }

    //Update an answer
    public function update(Request $request, QuizAnswer $answer)
    {
        $validator = Validator::make($request->all(),[
            'question_id' => 'required|exists:quiz_questions,id',
            'answer' => 'required|string|max:255',
            'is_correct' => 'required|boolean',
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'error' => $validator->messages(),
            ], 422);
        }

        $answer->update([
            'question_id' => $request->question_id,
            'answer' => $request->answer,
            'is_correct' => $request->is_correct,
        ]);

        return response()->json([
            'message' => 'Answer updated successfully',
            'data' => new QuizAnswerResource($answer)
        ]);
    }

    //Delete an answer
    public function destroy(QuizAnswer $answer)
    {
        $answer->delete();
        return response()->json([
            'message' => 'Answer deleted successfully'
        ]);
    }
}
