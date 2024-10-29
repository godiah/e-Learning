<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizQuestionResource;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuizQuestionController extends Controller
{
    use AuthorizesRequests;

    //Display all questions for a quiz
    public function index(Quiz $quiz)
    {
        $user = request()->user();

        $question = $quiz->questions();
        
        if ($user && ($user->id === $quiz->instructor_id)) {
            $question->with('answers');
        }
        
        $question = $question->get();

        if($question->count() > 0)
        {
            return QuizQuestionResource::collection($question);
        }
        else
        {
            return response()->json(['message' => 'No available questions found'], 404);
        }
    }

    //Display a single quiz question
    public function show(Quiz $quiz, QuizQuestion $question) 
    {
        if ($question->quiz_id !== $quiz->id) {
            return response()->json([
                'message' => 'Question not found in this quiz'
            ], 404);
        }

        $user = request()->user();

        if ($user && ($user->id === $quiz->instructor_id)) {
            $question->load('answers');
        }

        return new QuizQuestionResource($question);
    }

    //Add a question
    public function store(Request $request, Quiz $quiz)
    {
        
        try {
            $this->authorize('manage', $quiz);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized access!'], 403);
        }

        $validator = Validator::make($request->all(),[              
            'question' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($quiz) {
                    $exists = QuizQuestion::where('quiz_id', $quiz->quiz_id)
                        ->where('question', $value)
                        ->exists();
                    if ($exists) {
                        $fail("The question already exists!");
                    }
                },
            ],

            'answers' => 'required|array|min:1',
            'answers.*.answer' => 'required|string|max:255',
            'answers.*.is_correct' => 'required|boolean',                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->messages(),
            ], 422);
        }

        $hasCorrectAnswer = collect($request->answers)->contains('is_correct', true);
        if (!$hasCorrectAnswer) {
            return response()->json([
                'message' => 'At least one answer must be marked as correct',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $quiz) {
                $question = $quiz->questions()->create([
                    'question' => $request->question,
                ]);

                // Create answers
                foreach ($request->answers as $answerData) {
                    $question->answers()->create([
                        'answer' => $answerData['answer'],
                        'is_correct' => $answerData['is_correct'],
                    ]);
                }

                return $question;
            });

            // Load the answers relation for the resource
            $result->load('answers');

            return response()->json([
                'message' => 'Question and answers created successfully',
                'data' => new QuizQuestionResource($result)
            ], 201);
        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create question and answers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Update a question
    public function update(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        try {
            $this->authorize('manage', $quiz);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized access!'], 403);
        }        

        $validator = Validator::make($request->all(),[  
            'question' => [
                'string',
                'sometimes',
                function ($attribute, $value, $fail) use ($quiz, $question) {
                    $exists = QuizQuestion::where('quiz_id', $quiz->id)
                        ->where('question', $value)
                        ->where('id', '!=', $question->id) 
                        ->exists();

                    if ($exists) {
                        $fail("The question already exists!");
                    }
                },
            ],
            'answers' => 'required|array|min:1',
            'answers.*.id' => 'sometimes|exists:quiz_answers,id',
            'answers.*.answer' => 'required|string|max:255',
            'answers.*.is_correct' => 'required|boolean',                  
        ]);
        
        if($validator->fails())
        {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->messages(),
            ], 422);
        }

        $hasCorrectAnswer = collect($request->answers)->contains('is_correct', true);
        if (!$hasCorrectAnswer) {
            return response()->json([
                'message' => 'At least one answer must be marked as correct',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $question) {
                // Update question
                $question->update([
                    'question' => $request->question,
                ]);

                // Get existing answer IDs
                $existingAnswerIds = $question->answers->pluck('id')->toArray();
                $updatedAnswerIds = [];

                // Update or create answers
                foreach ($request->answers as $answerData) {
                    if (isset($answerData['id'])) {
                        // Update existing answer
                        $answer = QuizAnswer::find($answerData['id']);
                        if ($answer) {
                            $answer->update([
                                'answer' => $answerData['answer'],
                                'is_correct' => $answerData['is_correct'],
                            ]);
                            $updatedAnswerIds[] = $answer->id;
                        }
                    } else {
                        // Create new answer
                        $answer = $question->answers()->create([
                            'answer' => $answerData['answer'],
                            'is_correct' => $answerData['is_correct'],
                        ]);
                        $updatedAnswerIds[] = $answer->id;
                    }
                }

                // Delete answers that weren't updated or created
                $answersToDelete = array_diff($existingAnswerIds, $updatedAnswerIds);
                QuizAnswer::whereIn('id', $answersToDelete)->delete();

                return $question;
            });

            $result->load('answers');

            return response()->json([
                'message' => 'Question and answers updated successfully',
                'data' => new QuizQuestionResource($result)
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update question and answers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Delete a question
    public function destroy(Quiz $quiz, QuizQuestion $question)
    {
        $this->authorize('manage', $quiz);
        
        try {
            DB::transaction(function () use ($question) {                
                $question->answers()->delete();
                $question->delete();
            });

            return response()->json([
                'message' => 'Question and associated answers deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete question and answers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
