<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Fallback Route untuk melayani file storage public (misal: logo perusahaan)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    $type = mime_content_type($filePath) ?: 'image/png';
    return response()->file($filePath, [
        'Content-Type' => $type,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');
