<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

// Public routes for frontend fetching
Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'index']);
Route::get('/pricing/{id}', [\App\Http\Controllers\PricingController::class, 'show']);
Route::get('/modules', [\App\Http\Controllers\ModuleController::class, 'index']);
Route::get('/modules/{id}', [\App\Http\Controllers\ModuleController::class, 'show']);
Route::get('/faq', [\App\Http\Controllers\FaqController::class, 'index']);
Route::post('/orders', [\App\Http\Controllers\ClientOrderController::class, 'store']);

// Midtrans Webhook Notification
Route::match(['get', 'post'], '/midtrans/notification', [\App\Http\Controllers\InvoiceController::class, 'handleWebhook']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);

    // CMS CRUD Routes
    Route::post('/pricing', [\App\Http\Controllers\PricingController::class, 'store']);
    Route::put('/pricing/{id}', [\App\Http\Controllers\PricingController::class, 'update']);
    Route::delete('/pricing/{id}', [\App\Http\Controllers\PricingController::class, 'destroy']);

    Route::post('/modules', [\App\Http\Controllers\ModuleController::class, 'store']);
    Route::put('/modules/{id}', [\App\Http\Controllers\ModuleController::class, 'update']);
    Route::delete('/modules/{id}', [\App\Http\Controllers\ModuleController::class, 'destroy']);

    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\UserController::class, 'destroy']);

    Route::post('/faq', [\App\Http\Controllers\FaqController::class, 'store']);
    Route::put('/faq/{id}', [\App\Http\Controllers\FaqController::class, 'update']);
    Route::delete('/faq/{id}', [\App\Http\Controllers\FaqController::class, 'destroy']);

    Route::get('/orders', [\App\Http\Controllers\ClientOrderController::class, 'index']);
    Route::get('/orders/{id}', [\App\Http\Controllers\ClientOrderController::class, 'show']);
    Route::put('/orders/{id}', [\App\Http\Controllers\ClientOrderController::class, 'update']);
    Route::delete('/orders/{id}', [\App\Http\Controllers\ClientOrderController::class, 'destroy']);

    // Invoice routes
    Route::get('/orders/{id}/invoices', [\App\Http\Controllers\InvoiceController::class, 'indexByOrder']);
    Route::post('/orders/{id}/invoices', [\App\Http\Controllers\InvoiceController::class, 'storeForOrder']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'show']);
    Route::post('/invoices/{id}/payment-link', [\App\Http\Controllers\InvoiceController::class, 'generatePaymentLink']);
    Route::post('/invoices/{id}/check-status', [\App\Http\Controllers\InvoiceController::class, 'checkStatus']);
});
