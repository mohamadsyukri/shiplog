<?php

use App\Http\Controllers\Board\BoardController;
use Illuminate\Support\Facades\Route;

// The home page, open to every signed-in user (config/access.php open_routes).
Route::get('/', BoardController::class)->name('board.index');
