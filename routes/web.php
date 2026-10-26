<?php

use App\Http\Controllers\PublicPropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPropertyController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
