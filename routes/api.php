<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AssignmentSubmissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DiscussionController;
use App\Http\Controllers\Api\DiscussionReplyController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\InstructorCourseController;
use App\Http\Controllers\Api\LessonsController;
use App\Http\Controllers\Api\QuizAnswerController;
use App\Http\Controllers\Api\QuizAttemptController as ApiQuizAttemptController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizQuestionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UserAssignmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserQuizAttemptController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\InstructorMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerificationCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware(['auth:sanctum'])->group(function(){
    Route::post('/profile', [AuthController::class, 'profile']);
    Route::post('/profile/picture', [AuthController::class, 'upload']);
    Route::delete('/profile/picture', [AuthController::class, 'deleteProfilePicture']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/change-email', [AuthController::class, 'changeEmail']);
    Route::post('/verify-email-change', [AuthController::class, 'verifyEmailChange']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/instructor-application', [InstructorController::class, 'instructorApplication']);

    //Admin Routes
    Route::middleware([AdminMiddleware::class])->group(function () {
        Route::get('/instructor-applications', [InstructorController::class, 'index']);
        Route::post('/instructor-applications/{application}/approve', [InstructorController::class, 'approve']);
        Route::post('/instructor-applications/{application}/reject', [InstructorController::class, 'reject']);

        Route::post('/admin/create', [UserController::class, 'createAdmin']);
        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::get('/users/{user}/roles', [UserController::class, 'getUserRoles']);

        Route::apiResource('categories', CategoriesController::class);

        Route::put('/courses/approve/{courseApproval}', [CourseController::class, 'approveCourse']);
        Route::get('/course-applications', [CourseController::class, 'courseApprovalRequests']);
        
    });

    // Instructor Routes
    Route::prefix('instructor')->group(function () {
        Route::get('/courses', [InstructorCourseController::class, 'getCourses']);
        Route::get('/courses/{course}/assignments', [InstructorCourseController::class, 'getCourseAssignments']);
        Route::get('/assignments/{assignment}/submissions', [InstructorCourseController::class, 'getSubmissionsForAssignment']);
    });

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::post('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::get('/courses/{course}/lessons', [LessonsController::class, 'index']);
    Route::post('/courses/{course}/lessons', [LessonsController::class, 'store']);
    Route::get('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'show']);
    Route::post('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'update']);
    Route::delete('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'destroy']);

    Route::get('/lessons/{lesson}/quiz', [QuizController::class, 'index']);
    Route::get('/lessons/{lesson}/quiz/{quiz}', [QuizController::class, 'show']);
    Route::post('/lessons/{lesson}/quiz', [QuizController::class, 'store']);
    Route::put('/lessons/{lesson}/quiz/{quiz}', [QuizController::class, 'update']);
    Route::delete('/lessons/{lesson}/quiz/{quiz}', [QuizController::class, 'destroy']);

    Route::get('/quiz/{quiz}/questions', [QuizQuestionController::class, 'index']);
    Route::get('/quiz/{quiz}/questions/{question}', [QuizQuestionController::class, 'show']);
    Route::post('/quiz/{quiz}/questions', [QuizQuestionController::class, 'store']);
    Route::patch('/quiz/{quiz}/questions/{question}', [QuizQuestionController::class, 'update']);
    Route::delete('/quiz/{quiz}/questions/{question}', [QuizQuestionController::class, 'destroy']);

    Route::get('/lessons/{lesson}/assignments', [AssignmentController::class, 'index']);
    Route::get('/lessons/{lesson}/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::post('/lessons/{lesson}/assignments', [AssignmentController::class, 'store']);
    Route::patch('/lessons/{lesson}/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::delete('/lessons/{lesson}/assignments/{assignment}', [AssignmentController::class, 'destroy']);

    Route::post('assignments/{assignment}/submit', [AssignmentSubmissionController::class, 'submit']);
    Route::patch('assignments/{assignment}/submissions/{submission}/allow-resubmission', [AssignmentSubmissionController::class, 'allowResubmission']);
    Route::patch('assignment-submissions/{submission}/grade',[AssignmentSubmissionController::class, 'grade']);

    

    
    Route::apiResource('enrollment', EnrollmentController::class);

    Route::apiResource('attempt', ApiQuizAttemptController::class);
    Route::apiResource('userattempts', UserQuizAttemptController::class);
    Route::apiResource('assignments', AssignmentController::class);
    //Route::apiResource('user-assignments', UserAssignmentController::class);

    Route::get('/calculate-score/{quizId}/{userId}', [UserQuizAttemptController::class, 'calculateScore']);
    Route::post('/quiz/calculate-score', [UserQuizAttemptController::class, 'calculateScore']);
    Route::delete('/delete-answers', [UserQuizAttemptController::class, 'deleteUserAnswers']);

    Route::get('/courses/{courseId}/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/courses/{courseId}/discussions', [DiscussionController::class, 'index']);
    Route::post('/discussions', [DiscussionController::class, 'store']);
    Route::put('/discussions/{discussion}', [DiscussionController::class, 'update']);
    Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy']);

    Route::get('/discussions/{discussionId}/replies', [DiscussionReplyController::class, 'index']);
    Route::post('/replies', [DiscussionReplyController::class, 'store']);
    Route::put('/replies/{discussionReply}', [DiscussionReplyController::class, 'update']);
    Route::delete('/replies/{discussionReply}', [DiscussionReplyController::class, 'destroy']);

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
//Route::apiResource('reviews', ReviewController::class);
//Route::apiResource('discussions', DiscussionController::class);
//Route::apiResource('replies', DiscussionReplyController::class);