<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserEventController;

Route::get('/', [UserEventController::class, 'dashboard'])->name('kare.dashboard');
Route::get('/halls', [UserEventController::class, 'halls'])->name('kare.halls');
Route::get('/book', [UserEventController::class, 'create'])->name('kare.book');
Route::get('/book/{hallId}', [UserEventController::class, 'create'])->name('kare.book.withHall');
Route::post('/book', [UserEventController::class, 'store'])->name('kare.book.store');
Route::get('/my-bookings', [UserEventController::class, 'myBookings'])->name('kare.myBookings');
Route::post('/check-availability', [UserEventController::class, 'checkAvailability'])->name('kare.checkAvailability');

// Quick filtered views
Route::get('/bookings/pending', fn() => app(App\Http\Controllers\UserEventController::class)->myBookings(request()->merge(['status' => 'pending'])))
    ->name('kare.bookings.pending');
Route::get('/bookings/approved', fn() => app(App\Http\Controllers\UserEventController::class)->myBookings(request()->merge(['status' => 'approved'])))
    ->name('kare.bookings.approved');
Route::get('/bookings/rejected', fn() => app(App\Http\Controllers\UserEventController::class)->myBookings(request()->merge(['status' => 'rejected'])))
    ->name('kare.bookings.rejected');
Route::get('/bookings/upcoming', fn() => app(App\Http\Controllers\UserEventController::class)->myBookings(request()->merge(['scope' => 'upcoming'])))
    ->name('kare.bookings.upcoming');

// User-facing list of submitted cancellation requests (read-only status)
Route::get('/cancel-requests', [UserEventController::class, 'cancelRequests'])
    ->name('kare.cancelRequests');
