<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\UserQuizAttempt;
use Illuminate\Http\Request;

class UserQuizAttemptController extends Controller
{
    // Store user answers
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'question_id' => 'required|exists:quiz_questions,id',
            'selected_answer_id' => 'required|exists:quiz_answers,id'
        ]);

        $userAnswer = UserQuizAttempt::create($validated);

        return response()->json([
            'message' => 'User answer saved successfully',
            'data' => $userAnswer
        ]);
    }

    // Check answers and calculate score
    public function calculateScore(Request $request)
    {
        $quizId = $request->input('quiz_id');
        $userId = $request->input('user_id');
        $maxAttempts = 3;

        // Fetch the latest quiz attempt
        $lastQuizAttempt = QuizAttempt::where('quiz_id', $quizId)
                                      ->where('user_id', $userId)
                                      ->latest('attempt_number')
                                      ->first();

        // Check if the user has reached the maximum attempts
        if ($lastQuizAttempt && $lastQuizAttempt->attempt_number >= $maxAttempts) {
            return response()->json([
                'message' => 'Maximum number of attempts reached.',
            ], 403);
        }

        // Determine the start time for the current set of answers
        $previousEndTime = $lastQuizAttempt ? $lastQuizAttempt->end_time : null;

        // Fetch user answers submitted after the end of the last attempt
        $userAnswersQuery = UserQuizAttempt::where('quiz_id', $quizId)
                                            ->where('user_id', $userId);

        if ($previousEndTime) {
            $userAnswersQuery->where('created_at', '>', $previousEndTime);
        }

        $userAnswers = $userAnswersQuery->select('question_id', 'selected_answer_id')
                                        ->get()
                                        ->unique('question_id');

        $totalQuestions = $userAnswers->count();
        $correctAnswers = 0;

        // Compare the user's answers with the correct answers
        foreach ($userAnswers as $userAnswer) {
            $correctAnswer = QuizAnswer::where('question_id', $userAnswer->question_id)
                                       ->where('is_correct', 1)
                                       ->first();

            if ($correctAnswer && $correctAnswer->id == $userAnswer->selected_answer_id) {
                $correctAnswers++;
            }
        }

        // Calculate the score as a percentage
        $score = ($totalQuestions > 0) ? ($correctAnswers / $totalQuestions) * 100 : 0;

        // Create a new quiz attempt with the incremented attempt number
        $newAttemptNumber = $lastQuizAttempt ? $lastQuizAttempt->attempt_number + 1 : 1;

        $quizAttempt = QuizAttempt::create([
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'attempt_number' => $newAttemptNumber,
            'start_time' => now(),
            'end_time' => now(),
            'score' => $score,
        ]);

        return response()->json([
            'message' => 'Score calculated and quiz attempt updated',
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score' => $score,
            'quiz_attempt' => $quizAttempt,
        ]);
    }

    // Delete all user answers tied to a specific quiz
    public function deleteUserAnswers(Request $request)
    {
        // Validate the input
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'user_id' => 'required|exists:users,id',
        ]);
    
        $quizId = $request->input('quiz_id');
        $userId = $request->input('user_id');
    
        // Query to delete user answers for the specified quiz
        $query = UserQuizAttempt::where('quiz_id', $quizId);
    
        // If a user ID is provided, delete answers only for that user
        if ($userId) {
            $query->where('user_id', $userId);
        }
    
        // Perform the deletion
        $deletedRows = $query->delete();
    
        // Return a response indicating how many records were deleted
        return response()->json([
            'message' => $deletedRows > 0 ? 'User answers deleted successfully' : 'No answers found to delete',
            'deleted_rows' => $deletedRows
        ]);
    }

}
