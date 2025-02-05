<?php

use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\AffiliateLinkController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AssignmentSubmissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\ConversionTrackingController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseDiscountController;
use App\Http\Controllers\Api\CourseProgressController;
use App\Http\Controllers\Api\DiscussionController;
use App\Http\Controllers\Api\DiscussionReplyController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\InstructorCourseController;
use App\Http\Controllers\Api\LessonProgressController;
use App\Http\Controllers\Api\LessonsController;
use App\Http\Controllers\Api\QuizAnswerController;
use App\Http\Controllers\Api\QuizAttemptController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizQuestionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserQuizAttemptController;
use App\Http\Controllers\Api\VideoProgressController;
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

Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google-callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Affiliate Tracking Endpoints
Route::middleware(['antiFraud'])->group(function () {
    Route::get('/r/{shortCode}', [TrackingController::class, 'trackClick']);
});

Route::post('/conversions', [TrackingController::class, 'recordConversion']);

// Categories & Courses
Route::get('categories', [CategoriesController::class, 'index'])->name('categories.index');
Route::get('categories/{category}', [CategoriesController::class, 'show'])->name('categories.show');
Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');


Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/profile', [AuthController::class, 'profile']); //change
    Route::post('/profile/picture', [AuthController::class, 'upload']);
    Route::delete('/profile/picture', [AuthController::class, 'deleteProfilePicture']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']); //change

    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/change-email', [AuthController::class, 'changeEmail']);
    Route::post('/verify-email-change', [AuthController::class, 'verifyEmailChange']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/instructor-application', [InstructorController::class, 'instructorApplication']);

    /**
     * Administrator Routes
     */
    // Super Admin
    Route::middleware('admin')->group(function () {
        Route::post('/admin/create', [UserController::class, 'createAdmin']);        
        Route::post('/admin/new-role', [UserController::class, 'createRole']);        
    });

    // User Management Admin Routes
    Route::middleware('admin:admin, user-admin')->group(function () {
        Route::get('/instructor-applications', [InstructorController::class, 'index']);
        Route::post('/instructor-applications/{application}/approve', [InstructorController::class, 'approve']);
        Route::post('/instructor-applications/{application}/reject', [InstructorController::class, 'reject']);

        Route::get('/affiliate-applications', [AffiliateController::class, 'index']);
        Route::post('/affiliates/{affiliate}/approve', [AffiliateController::class, 'approve']);
        Route::post('/affiliates/{affiliate}/reject', [AffiliateController::class, 'reject']);
        Route::post('/affiliates/{affiliate}/suspend', [AffiliateController::class, 'suspend']);
        Route::get('/affiliates-suspended', [AffiliateController::class, 'viewSuspended']);
        Route::post('/affiliate/{affiliate}/lift-suspension', [AffiliateController::class, 'liftSuspension']); 

        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::get('/users/{user}/roles', [UserController::class, 'getUserRoles']);
    });

    // Content Management Admin Routes
    Route::middleware('admin:admin, content-admin')->group(function () {
        Route::post('categories', [CategoriesController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

        Route::patch('/courses/approve/{courseApproval}', [CourseController::class, 'approveCourse']);
        Route::get('/course-applications', [CourseController::class, 'courseApprovalRequests']);
    });

    // Finance Management Admin Routes
    Route::middleware('admin:admin, finance-admin')->group(function () {
        Route::patch('affiliate/links/{affiliateLink}/commission', [AffiliateLinkController::class, 'updateCommissionRate']);
        //Route::post('/affiliate/purchases/{id}/process-payment', [AffiliatePurchaseController::class, 'processPayment']);
    });

    // Instructor Routes
    Route::prefix('instructor')->group(function () {
        Route::get('/courses', [InstructorCourseController::class, 'getCourses']);
        Route::get('/courses/{course}/assignments', [InstructorCourseController::class, 'getCourseAssignments']);
        Route::get('/assignments/{assignment}/submissions', [InstructorCourseController::class, 'getSubmissionsForAssignment']);
        Route::get('/courses/{course}/quizzes', [InstructorCourseController::class, 'getCourseQuizzes']);
        Route::get('/courses/{course}/quizzes/{quiz}/analytics', [InstructorCourseController::class, 'getQuizAnalytics']);
    });

    // Affiliate Routes
    Route::prefix('affiliate')->group(function() {
        Route::post('/links/{course}', [AffiliateLinkController::class, 'generate']);
        Route::get('/links', [AffiliateLinkController::class, 'index']);
        Route::get('/links/{affiliateLink}/stats', [AffiliateLinkController::class, 'getStats']);
        Route::post('/apply', [AffiliateController::class, 'affiliateApplication']);
        Route::get('/stats', [AffiliateController::class, 'getOverallStats']);
    });

    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::post('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::get('/search', [CourseController::class, 'filterCourses']);

    Route::get('/courses/{course}/lessons', [LessonsController::class, 'index']);
    Route::post('/courses/{course}/lessons', [LessonsController::class, 'store']);
    Route::get('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'show']);
    Route::post('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'update']);
    Route::delete('/courses/{course}/lessons/{lesson}', [LessonsController::class, 'destroy']);
    Route::delete('/courses/{course}/lessons/{lesson}/subcontents/{subcontent}', [LessonsController::class, 'destroySubcontent']);

    Route::post('/courses/{course}/certificate', [CertificateController::class, 'generate']);
    Route::get('/certificates/verify/{number}', [CertificateController::class, 'verify'])->name('certificates.verify');

    Route::get('/courses/{course}/completion-status', [CourseProgressController::class, 'show']);
    Route::get('/courses/{course}/progress/reset', [CourseProgressController::class, 'reset']);

    Route::post('/video-progress/{subcontentId}', [VideoProgressController::class, 'updateProgress']); // updates watch time for videos
    Route::get('courses/{course}/progress', [VideoProgressController::class, 'viewProgress']);

    //Route::get('lessons/{lesson}/progress', [LessonProgressController::class, 'show']);
    //Route::patch('lessons/{lesson}/progress', [LessonProgressController::class, 'update']);
    //Route::get('courses/{course}/progress', [LessonProgressController::class, 'getCourseProgress']);

    Route::get('/lessons/{lesson}/quiz', [QuizController::class, 'index']);
    Route::get('/lessons/{lesson}/quiz/{quiz}', [QuizController::class, 'show']);
    Route::post('/lessons/{lesson}/quiz', [QuizController::class, 'store']);
    Route::patch('/lessons/{lesson}/quiz/{quiz}', [QuizController::class, 'update']);
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
    Route::get('courses/{course}/assignments/{assignment}/submission', [AssignmentSubmissionController::class, 'viewSubmission']);

    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'withdraw']);
    Route::get('/courses/{course}/enrollments', [EnrollmentController::class, 'getCourseEnrollments']);
    Route::get('/user/my-enrollments', [EnrollmentController::class, 'getStudentEnrollments']);
    Route::patch('/my-enrollments/{enrollment}/complete', [EnrollmentController::class, 'markAsCompleted']);

    Route::post('/quizzes/{quiz}/start', [QuizAttemptController::class, 'start']);
    Route::post('/quiz-attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);
    Route::get('/quizzes/{quiz}/results', [QuizAttemptController::class, 'results']);
    Route::delete('/quiz/{attempt}/delete', [QuizAttemptController::class, 'destroy']);    

    Route::get('/courses/{course}/reviews', [ReviewController::class, 'index']);
    Route::post('/courses/{course}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/courses/{course}/discussions', [DiscussionController::class, 'index']);
    Route::get('/courses/{course}/discussions/{discussion}', [DiscussionController::class, 'show']);
    Route::post('/courses/{course}/discussions', [DiscussionController::class, 'store']);
    Route::put('/discussions/{discussion}', [DiscussionController::class, 'update']);
    Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy']);

    Route::get('/discussions/{discussion}/replies', [DiscussionReplyController::class, 'index']);
    Route::post('/discussions/{discussion}/replies', [DiscussionReplyController::class, 'store']);
    Route::patch('/replies/{discussionReply}', [DiscussionReplyController::class, 'update']);
    Route::delete('/replies/{discussionReply}', [DiscussionReplyController::class, 'destroy']);

    Route::get('courses/{course}/discounts', [CourseDiscountController::class, 'index']);
    Route::get('discounts/{discount}', [CourseDiscountController::class, 'show']);
    Route::post('courses/{course}/discounts', [CourseDiscountController::class, 'store']);
    Route::patch('discounts/{discount}', [CourseDiscountController::class, 'update']);
    Route::delete('discounts/{discount}', [CourseDiscountController::class, 'destroy']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'viewCart']);
        Route::post('/add', [CartController::class, 'addToCart']);
        Route::post('/checkout', [CartController::class, 'checkout']);
        Route::delete('/items/{cartItemId}', [CartController::class, 'removeFromCart']);
    });

    Route::prefix('wishlist')->group(function () {
        Route::get('/', [CartController::class, 'viewWishlist']);
        Route::post('/add', [CartController::class, 'addToWishlist']);
        Route::delete('remove/{wishlistId}', [CartController::class, 'removeFromWishlist']);
    });

    // Record Conversions & Affiliate Commissions
    Route::post('/record-conversion/{order}', [ConversionTrackingController::class, 'recordConversionFromCheckout']);
    

    //Route::post('/affiliate/purchases/track', [AffiliatePurchaseController::class, 'track']);
    

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