<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAttemptResource;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizAttemptController extends Controller
{
    //Display  quiz attempt
    public function index()
    {
        $attempt = QuizAttempt::get();
        if($attempt->count() > 0)
        {
            return QuizAttemptResource::collection($attempt);
        }
        else
        {
            return response()->json(['message' => 'No available attempts found'], 404);
        }
    }

    //View single attempt
    public function show(QuizAttempt $attempt)
    {
        return new QuizAttemptResource($attempt);
    }

    // Add an attempt
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',                  
            'quiz_id' => 'required|exists:quizzes,id',                  
            'attempt_number' => 'required',                  
            'start_time' => 'required',                  
            'end_time' => 'required',                  
            'score' => 'required',                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'All Fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $attempt = QuizAttempt::create([
            'user_id' => $request->user_id,
            'quiz_id' => $request->quiz_id,            
            'attempt_number' => $request->attempt_number,            
            'start_time' => $request->start_time,            
            'end_time' => $request->end_time,           
            'score' => $request->score,
        ]);

        return response()->json([
            'message' => 'Attempt created successfully',
            'data' => new QuizAttemptResource($attempt)
        ]);
    }

    // Update an attempt
    public function update(Request $request, QuizAttempt $attempt)
    {
        $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',                  
        'quiz_id' => 'required|exists:quizzes,id',                  
        'attempt_number' => 'required',                  
        'start_time' => 'required',                  
        'end_time' => 'required',                  
        'score' => 'required',                 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are mandatory',
                'error' => $validator->messages(),
            ], 422);
        }

        $attempt->update([
            'user_id' => $request->user_id,
            'quiz_id' => $request->quiz_id,            
            'attempt_number' => $request->attempt_number,            
            'start_time' => $request->start_time,            
            'end_time' => $request->end_time,            
            'score' => $request->score,
        ]);

        return response()->json([
            'message' => 'Attempt updated successfully',
            'data' => new QuizAttemptResource($attempt),
        ]);
    }

    //Delete an attempt
    public function destroy(QuizAttempt $attempt)
    {
        $attempt->delete();
        return response()->json([
            'message' => 'Attempt deleted successfully'
        ]);
    }

}
