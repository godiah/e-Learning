<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonsController;
use App\Http\Controllers\Api\QuizAnswerController;
use App\Http\Controllers\Api\QuizAttemptController as ApiQuizAttemptController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizQuestionController;
use App\Http\Controllers\Api\UserAssignmentController;
use App\Http\Controllers\Api\UserQuizAttemptController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Middleware\AdminOrInstructorMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::post('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('enrollment', EnrollmentController::class);
    Route::apiResource('lesson', LessonsController::class);
    Route::apiResource('quiz', QuizController::class);
    Route::apiResource('question', QuizQuestionController::class);
    Route::apiResource('answer', QuizAnswerController::class);
    Route::apiResource('attempt', ApiQuizAttemptController::class);
    Route::apiResource('userattempts', UserQuizAttemptController::class);
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('user-assignments', UserAssignmentController::class);

    Route::get('/calculate-score/{quizId}/{userId}', [UserQuizAttemptController::class, 'calculateScore']);
    Route::post('/quiz/calculate-score', [UserQuizAttemptController::class, 'calculateScore']);
    Route::delete('/delete-answers', [UserQuizAttemptController::class, 'deleteUserAnswers']);

});


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Route for fetching all courses (no middleware applied)
//Route::get('courses', [CourseController::class, 'index']);
//Route::get('courses/{course}', [CourseController::class, 'show']);

// Routes that require admin or instructor access
// Route::middleware([AdminOrInstructorMiddleware::class])->group(function () {
//     Route::post('api/courses', [CourseController::class, 'store']);
//     Route::put('courses/{course}', [CourseController::class, 'update']);
//     Route::delete('courses/{course}', [CourseController::class, 'destroy']);
// });
