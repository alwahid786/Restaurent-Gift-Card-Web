<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;

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

// Without Auth Routes 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send_otp', [AuthController::class, 'sendOtp']);
Route::post('/verify_otp', [AuthController::class, 'verifyOtp']);

// Auth Protected Routes for User Site
Route::middleware('auth:api')->group(function () {
    Route::post('/reset_password', [AuthController::class, 'resetPassword']);
    Route::post('/change_password', [AuthController::class, 'changePassword']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/edit_profile', [UserController::class, 'editProfile']);
    Route::post('/create_contact', [UserController::class, 'createContact']);
    Route::get('/contact_list', [UserController::class, 'contactList']);
    Route::get('/all_restaurents', [GiftController::class, 'restaurentList']);
    Route::post('/restaurent_menu', [GiftController::class, 'menuImages']);
    Route::get('/sent_gifts', [GiftController::class, 'sentGifts']);
    Route::get('/received_gifts', [GiftController::class, 'receivedGifts']);
    Route::post('/payment_intent', [TransactionController::class, 'paymentIntent']);
    Route::post('/create_transaction', [TransactionController::class, 'createTransaction']);
    Route::get('/notification_list', [NotificationController::class, 'notificationList']);
});

// Auth Protected Routes for Restaurent Site
Route::group(['prefix' => 'restaurent', 'middleware' => ['auth:api']], function () {
    Route::post('/gift_amount', [GiftController::class, 'giftAmount']);
    Route::post('/debit_voucher', [GiftController::class, 'debitVoucher']);
    Route::get('/dashboard', [UserController::class, 'restaurentDashboard']);
    Route::get('/transaction_history', [TransactionController::class, 'TransactionHistory']);
});

// Admin Side API's List 
Route::middleware('auth:api')->group(function () {
    Route::post('/create_restaurent', [AdminController::class, 'createRestaurent']);
});
