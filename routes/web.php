<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\RentalInquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPropertyController::class)->name('home');

// Form publik tanpa login: dibatasi 5 kiriman per jam per IP (lihat AppServiceProvider).
Route::post('inquiries', [RentalInquiryController::class, 'store'])
    ->middleware('throttle:rental-inquiries')
    ->name('rental-inquiries.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Halaman pemilik. Hak akses dicek per aksi lewat Policy, bukan hanya lewat awalan URL.
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('property', [PropertyController::class, 'edit'])->name('property.edit');
        Route::put('property', [PropertyController::class, 'update'])->name('property.update');
        Route::resource('rooms', RoomController::class)->except('show');
    });
});

require __DIR__.'/settings.php';
