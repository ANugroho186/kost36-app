<?php

use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintPhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner\ComplaintController as OwnerComplaintController;
use App\Http\Controllers\Owner\OccupantAccountController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\Owner\RoomPhotoController;
use App\Http\Controllers\Owner\TenancyController;
use App\Http\Controllers\Owner\TenancyOccupantController;
use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\RentalInquiryController;
use App\Http\Controllers\RoommateConsentController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPropertyController::class)->name('home');

// Form publik tanpa login: dibatasi 5 kiriman per jam per IP (lihat AppServiceProvider).
Route::post('inquiries', [RentalInquiryController::class, 'store'])
    ->middleware('throttle:rental-inquiries')
    ->name('rental-inquiries.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Dipakai penghuni dan pemilik. Siapa yang boleh dicek oleh TenancyPolicy::toggleRoommate.
    Route::put('tenancies/{tenancy}/accepts-roommate', RoommateConsentController::class)
        ->name('tenancies.accepts-roommate');

    // Keluhan penghuni tentang kamarnya sendiri (kamar diambil dari sewa yang sedang ditempati).
    Route::resource('complaints', ComplaintController::class)->only(['index', 'store']);

    // Foto keluhan di disk privat, untuk penghuni dan pemilik. Hak akses: ComplaintPolicy::view.
    Route::get('complaints/{complaint}/photos/{photo}', ComplaintPhotoController::class)
        ->scopeBindings()
        ->name('complaints.photos.show');

    // Halaman pemilik. Hak akses dicek per aksi lewat Policy, bukan hanya lewat awalan URL.
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('property', [PropertyController::class, 'edit'])->name('property.edit');
        Route::put('property', [PropertyController::class, 'update'])->name('property.update');
        // Tambah dan ubah kamar dibuka sebagai modal di halaman daftar, jadi tidak ada halaman create/edit.
        Route::resource('rooms', RoomController::class)->only(['index', 'store', 'update', 'destroy']);

        // scopeBindings: {photo} harus milik {room}, kalau tidak hasilnya 404.
        Route::scopeBindings()->group(function () {
            Route::post('rooms/{room}/photos', [RoomPhotoController::class, 'store'])->name('rooms.photos.store');
            Route::put('rooms/{room}/photos/{photo}/cover', [RoomPhotoController::class, 'cover'])->name('rooms.photos.cover');
            Route::delete('rooms/{room}/photos/{photo}', [RoomPhotoController::class, 'destroy'])->name('rooms.photos.destroy');
        });

        Route::resource('complaints', OwnerComplaintController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tenancies', TenancyController::class)->only(['index', 'store', 'show']);
        Route::post('tenancies/{tenancy}/occupants', [TenancyOccupantController::class, 'store'])
            ->name('tenancies.occupants.store');

        // scopeBindings: {occupant} harus penghuni dari {tenancy}, kalau tidak hasilnya 404.
        Route::scopeBindings()->group(function () {
            Route::post('tenancies/{tenancy}/occupants/{occupant}/account', [OccupantAccountController::class, 'store'])
                ->name('tenancies.occupants.account.store');
            Route::put('tenancies/{tenancy}/occupants/{occupant}/password', [OccupantAccountController::class, 'resetPassword'])
                ->name('tenancies.occupants.password.reset');
        });
    });
});

require __DIR__.'/settings.php';
