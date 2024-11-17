<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseProgressResource;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\QuizResponseResource;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizResponse;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QuizAttemptController extends Controller
{
    // Register an attempt
    public function start(Request $request, Quiz $quiz)
    {
        $user = request()->user();

        // Validate enrollment
        if (!$quiz->lesson->course->enrollments()
            ->where('user_id', $user->id)
            ->exists()) {
            return response()->json([
                'message' => 'You are not enrolled in this course'
            ], 403);
        }

        // Check lesson completion first
        if (!$quiz->isAvailableForUser($user)) {
            return response()->json([
                'message' => 'Please complete the lesson before attempting the quiz'
            ], 403);
        }

        // Check attempt limits
        $attemptCount = $quiz->attempts()
            ->where('user_id', $user->id)            
            ->count();

        if ($attemptCount >= $quiz->max_attempts) {
            return response()->json([
                'message' => 'No attempts remaining'
            ], 403);
        }

        // Check for ongoing attempts
        $ongoingAttempt = $quiz->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($ongoingAttempt) {
            return response()->json([
                'message' => 'You have an ongoing attempt',
                'attempt' => new QuizAttemptResource($ongoingAttempt)
            ], 409);
        }

        // Create new attempt
        $startTime = now();
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes($quiz->time_limit),
            'device_id' => $request->header('X-Device-ID'),
            'attempt_number' => $attemptCount + 1,
            'status' => 'in_progress'
        ]);

        return new QuizAttemptResource($attempt);
    }


    /**
     * Submit quiz answers manually
     */
    public function submit(Request $request, QuizAttempt $attempt)
    {
        // Authorize the user
        $user = $request->user();
        if ($attempt->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate attempt is in progress
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'message' => 'Quiz already submitted',
                'status' => $attempt->status
            ], 400);
        }

        // Get current time once to ensure consistency
        $currentTime = now();

        // Check if time has expired
        $isExpired = $currentTime->greaterThan($attempt->end_time);
        if ($isExpired) {
            Log::info("Quiz attempt ID {$attempt->id} for user ID {$attempt->user_id} was auto-submitted due to time expiration.");
            return $this->endAttempt($attempt);
        }

        // Validate incoming answers
        $validatedData = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer_id' => 'nullable|exists:quiz_answers,id',
        ]);

        // Use a lock to prevent double submissions
        return DB::transaction(function () use ($attempt, $validatedData, $currentTime) {
            // Acquire a lock for this attempt
            $lockKey = "quiz_attempt_{$attempt->id}_lock";
            if (!Cache::add($lockKey, true, 30)) { 
                return response()->json(['message' => 'Submission already in progress'], 409);
            }

            try {
                // Refresh attempt to get latest status
                $attempt->refresh();

                // Double-check status hasn't changed
                if ($attempt->status !== 'in_progress') {
                    return response()->json([
                        'message' => 'Quiz already submitted or timed out',
                        'status' => $attempt->status
                    ], 400);
                }

                Log::info("Processing answers for attempt {$attempt->id}", [
                    'answer_count' => count($validatedData['answers'])
                ]);

                // Process submitted answers
                foreach ($validatedData['answers'] as $answerData) {
                    $this->processAnswer($attempt, $answerData);
                }

                // Important: Commit the transaction here to ensure responses are saved
                DB::commit();

                // Now refresh the attempt to get the latest responses
                $attempt->refresh();

                // Calculate final score using the saved responses
                $scoreData = $this->calculateScore($attempt);

                // Update attempt without modifying start_time
                $attempt->updateQuietly([
                    'status' => $currentTime->greaterThan($attempt->end_time) ? 'timed_out' : 'completed',
                    'score' => $scoreData['percentage_score'],
                    'end_time' => $currentTime
                ]);

                // Update progress status based on score
                $course = $attempt->quiz->lesson->course;
                $attempt->progress_status = $attempt->score >= $course->pass_mark ? 'passed' : 'failed';
                $attempt->save();

                // Calculate course progress
                $progressService = new CourseProgressService();
                $progress = $progressService->calculateProgress($course->id, $attempt->user_id);

                return response()->json([
                    'message' => 'Quiz submitted successfully',
                    'score' => $attempt->score,
                    'status' => $attempt->status,
                    'progress_status' => $attempt->progress_status,
                    'course_progress' => new CourseProgressResource($progress),
                    'timing' => [
                        'start_time' => $attempt->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $attempt->end_time->format('Y-m-d H:i:s'),
                        'duration' => $attempt->start_time->diffInMinutes($attempt->end_time) . ' minutes'
                    ]
                ]);
            } finally {
                // Always release the lock
                Cache::forget($lockKey);
            }
        });
    }

    /**
     * End attempt automatically
     */
    public function endAttempt(QuizAttempt $attempt)
    {
        $user = request()->user();

        if ($attempt->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if attempt is already ended
        if (in_array($attempt->status, ['completed', 'timed_out'])) {
            Log::info("Attempt already ended", [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status
            ]);
            return response()->json([
                'message' => 'Attempt already ended',
                'status' => $attempt->status
            ], 400);
        }

        // Use a lock to prevent double submissions
        return DB::transaction(function () use ($attempt) {
            $lockKey = "quiz_attempt_{$attempt->id}_lock";

            Log::info("Attempting to acquire lock for attempt {$attempt->id}");

            if (!Cache::add($lockKey, true, 30)) {
                Log::warning("Lock acquisition failed for attempt {$attempt->id}");
                return response()->json(['message' => 'Submission already in progress'], 409);
            }

            try {
                // Refresh attempt to get latest status
                $attempt->refresh();

                if ($attempt->status !== 'in_progress') {
                    Log::info("Attempt status changed after refresh", [
                        'attempt_id' => $attempt->id,
                        'status' => $attempt->status
                    ]);
                    return response()->json([
                        'message' => 'Attempt already ended',
                        'status' => $attempt->status
                    ], 400);
                }

                // Calculate score and create missing responses
                $scoreData = $this->calculateScore($attempt, true);

                Log::info("Updating attempt with final score", [
                    'attempt_id' => $attempt->id,
                    'score' => $scoreData['percentage_score']
                ]);

                // Update attempt with timed_out status and original end_time
                $attempt->updateQuietly([
                    'status' => 'timed_out',
                    'score' => $scoreData['percentage_score'],
                    'end_time' => $attempt->end_time
                ]);

                Cache::forget($lockKey);

                return new QuizAttemptResource($attempt);
            } catch (\Exception $e) {
                Cache::forget($lockKey);
                throw $e;
            }
        });
    }

    /**
     * View quiz responses
     */
    public function results(Request $request, Quiz $quiz)
    {
        // Get the authenticated user
        $user = $request->user();

        // Fetch the latest quiz attempt for the given quiz and user
        $attempt = QuizAttempt::with(['responses.question', 'quiz'])
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->latest('end_time')
            ->first();

        // Check if the attempt exists
        if (!$attempt) {
            return response()->json(['message' => 'No results found for this quiz attempt.'], 404);
        }

        // Format the response data
        $resultData = [
            'quiz_name' => $attempt->quiz->title,
            'student_score' => $attempt->score,
            'status' => $attempt->status,
            'questions' => []
        ];

        // Get each response along with the question, student’s answer, and correct answer
        foreach ($attempt->responses as $response) {
            // Check if the question exists
            $question = $response->question;
            if (!$question) {
                $resultData['questions'][] = [
                    'question' => 'Question not found',
                    'selected_answer' => $response->selectedAnswer ? $response->selectedAnswer->text : 'Not answered',
                    'correct_answer' => 'N/A',
                    'is_correct' => false
                ];
                continue;
            }

            // Fetch the correct answer
            $correctAnswer = $question->answers()->where('is_correct', true)->first();

            $resultData['questions'][] = [
                'question' => $question->question,
                'selected_answer' => $response->selectedAnswer ? $response->selectedAnswer->answer : 'Not answered',
                'correct_answer' => $correctAnswer->answer ?? 'No correct answer specified',
                'is_correct' => $response->is_correct
            ];
        }

        // Include additional important information if needed
        $resultData['total_questions'] = count($resultData['questions']);
        $resultData['total_correct'] = count(array_filter($resultData['questions'], fn($q) => $q['is_correct']));

        return response()->json($resultData);
    }

    /**
     * Calculate score for the attempt
     * 
     * @param QuizAttempt $attempt
     * @param bool $createMissing Whether to create records for missing responses
     * @return array
     */
    
    private function calculateScore(QuizAttempt $attempt, bool $createMissing = false): array
    {
        $totalPossibleScore = 0;
        $earnedScore = 0;

        // Get all existing responses for this attempt first
        $existingResponses = $attempt->responses()
            ->whereNotNull('selected_answer_id')
            ->get()
            ->keyBy('quiz_question_id');

        Log::info("Calculating score for attempt {$attempt->id}", [
            'existing_responses_count' => $existingResponses->count(),
            'existing_responses' => $existingResponses->toArray()
        ]);

        // Get all questions with their correct answers
        $quizQuestions = $attempt->quiz->questions()->with(['answers' => function ($query) {
            $query->where('is_correct', true);
        }])->get();

        foreach ($quizQuestions as $question) {
            $totalPossibleScore += $question->weight;

            // Check if there's an existing response for this question
            if ($existingResponses->has($question->id)) {
                $response = $existingResponses->get($question->id);
                $earnedScore += $response->score;

                Log::info("Found existing response for question {$question->id}", [
                    'selected_answer_id' => $response->selected_answer_id,
                    'score' => $response->score
                ]);
            }
            // Only create missing responses for questions that don't have an existing response
            elseif ($createMissing) {
                Log::info("Creating missing response for question {$question->id}");

                // Create response for unanswered question
                QuizResponse::create([
                    'quiz_attempt_id' => $attempt->id,
                    'user_id' => $attempt->user_id,
                    'quiz_question_id' => $question->id,
                    'selected_answer_id' => null,
                    'score' => 0,
                    'is_correct' => false
                ]);
            }
        }

        $percentageScore = $totalPossibleScore > 0 
            ? round(($earnedScore / $totalPossibleScore) * 100, 2)
            : 0;

        Log::info("Score calculation complete", [
            'attempt_id' => $attempt->id,
            'raw_score' => $earnedScore,
            'total_possible' => $totalPossibleScore,
            'percentage_score' => $percentageScore
        ]);

        return [
            'raw_score' => $earnedScore,
            'total_possible' => $totalPossibleScore,
            'percentage_score' => $percentageScore
        ];
    }
    
    /**
     * Process a single answer submission
     */
    private function processAnswer(QuizAttempt $attempt, array $answerData): void
    {
        $questionId = $answerData['question_id'];
        $selectedAnswerId = $answerData['answer_id'] ?? null;

        Log::info("Processing answer for attempt {$attempt->id}", [
            'question_id' => $questionId,
            'selected_answer_id' => $selectedAnswerId
        ]);

        // Find the correct answer for this question
        $correctAnswer = QuizAnswer::where('question_id', $questionId)
            ->where('is_correct', true)
            ->first();

        // Determine score and correctness
        $isCorrect = $selectedAnswerId && $correctAnswer && $selectedAnswerId === $correctAnswer->id;
        $questionScore = $isCorrect ? $correctAnswer->question->weight : 0;

        // Create or update quiz response
        $response = QuizResponse::updateOrCreate(
            [
                'quiz_attempt_id' => $attempt->id,
                'quiz_question_id' => $questionId,
            ],
            [
                'user_id' => $attempt->user_id,
                'selected_answer_id' => $selectedAnswerId,
                'score' => $questionScore,
                'is_correct' => $isCorrect
            ]
        );

        Log::info("Saved response for attempt {$attempt->id}", [
            'question_id' => $questionId,
            'response_id' => $response->id,
            'selected_answer_id' => $selectedAnswerId,
            'is_correct' => $isCorrect,
            'score' => $questionScore
        ]);
    }

    //Delete an attempt
    public function destroy(Request $request, QuizAttempt $attempt)
    {
        $instructorId = $attempt->quiz->course->instructor_id;

        // Check if the authenticated user is the instructor
        if ($request->user()->id !== $instructorId) {
            return response()->json([
                'message' => 'Unauthorized action'
            ], 403);
        }
        $attempt->responses()->delete();
    
        // Delete the attempt itself
        $attempt->delete();
    
        return response()->json([
            'message' => 'Attempt and associated responses deleted successfully'
        ]);
    }

}
