<?php

use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\RentalInquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPropertyController::class)->name('home');

// Form publik tanpa login: dibatasi 5 kiriman per jam per IP (lihat AppServiceProvider).
Route::post('inquiries', [RentalInquiryController::class, 'store'])
    ->middleware('throttle:rental-inquiries')
    ->name('rental-inquiries.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
