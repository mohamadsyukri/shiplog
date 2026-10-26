<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Sign in, sign out and password reset come from Fortify (config/fortify.php).

// Every named route in here is also a permission (config/access.php, `php artisan permissions:sync`).
Route::middleware(['auth', 'route.permission'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    require __DIR__.'/modules/board.php';
    require __DIR__.'/modules/imports.php';
    require __DIR__.'/modules/announcements.php';
    require __DIR__.'/modules/admin.php';
});
