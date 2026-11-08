<?php

use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\TelegramConnectionController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Tidak ada route hapus akun sendiri: akun hanya dihapus lewat alur pemilik saat penghuni keluar.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])->name('notification-settings.edit');
    Route::put('settings/notifications', [NotificationSettingsController::class, 'update'])->name('notification-settings.update');
    Route::post('settings/notifications/telegram', [TelegramConnectionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('notification-settings.telegram.connect');
    Route::delete('settings/notifications/telegram', [TelegramConnectionController::class, 'destroy'])
        ->name('notification-settings.telegram.disconnect');
});
