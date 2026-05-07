<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordImageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/calendar');

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/records/range', [RecordController::class, 'range'])->name('records.range');
Route::get('/records/{date}', [RecordController::class, 'show'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('records.show');

Route::middleware('auth')->group(function () {
    Route::get('/records/create', [RecordController::class, 'create'])->name('records.create');
    Route::post('/records', [RecordController::class, 'store'])->name('records.store');
    Route::put('/records/{date}', [RecordController::class, 'update'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('records.update');
    Route::post('/records/{date}/images', [RecordImageController::class, 'store'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('records.images.store');
});
