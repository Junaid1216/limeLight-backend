<?php

use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailOtpController;
use App\Http\Controllers\Api\FeedBackController;
use App\Http\Controllers\Api\LineItemController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TrainingVideoController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SideMenueController;
use App\Http\Controllers\SideMenuPermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/roles', [RoleController::class, 'store']);

Route::post('/permissions', [PermissionController::class, 'store']);
Route::post('/sidemenue', [SideMenueController::class, 'store']);

Route::post('/permission-insert', [SideMenuPermissionController::class, 'assignPermissions']);

// seo routes
Route::post('/seo-bulk', [SeoController::class, 'storeBulk'])
     ->name('seo.bulk-update');


// LineItem Sync Route
Route::post('/line-items', [LineItemController::class, 'store']);


//Register
Route::post('/register', [AuthController::class, 'register']);

//Login
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sendotp', [AuthController::class, 'sendOtp']);
    Route::post('/verifyotp', [AuthController::class, 'verifyOtp']);
    Route::post('/resendotp', [AuthController::class, 'resendOtp']);
    Route::post('/resetpassword', [AuthController::class, 'resetPassword']);
    Route::post('/changepassword', [AuthController::class, 'changePassword']);
    Route::get('/getprofile', [AuthController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sale Staff Feedback
    Route::post('/staff-feedback', [FeedBackController::class, 'stafffeedback']);
    // ASM Feedback
    Route::post('/asm-feedback', [FeedBackController::class, 'asmfeedback']);

    //Get training videos
    Route::get('/training-videos', [TrainingVideoController::class, 'getTrainingVideos']);

    //Notifications
    Route::get('/notifications', [NotificationController::class, 'getUserNotifications']);
});



Route::middleware('auth:sanctum')->group(function () {

    // Password reset for Admin & SubAdmin via API
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
    Route::get('/verify-reset-token/{token}', [AuthController::class, 'verifyResetToken']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

	Route::post('/send-otp', [EmailOtpController::class, 'sendOtp']);
Route::post('/verify-otp', [EmailOtpController::class, 'verifyOtp']);
Route::post('/register-user', [EmailOtpController::class, 'registerUser']);
Route::post('/submit-contact-us', [ContactUsController::class, 'Submitcontact'])->name('contact.send');

  Route::post('/update-profile', [EmailOtpController::class, 'requestUpdateOtp']);
    Route::post('/update-profile-verify', [EmailOtpController::class, 'verifyAndUpdateContact']);
    Route::get('/get-logged-in-user-info', [EmailOtpController::class, 'getLoggedInUserInfo']);
});

