
<?php

use App\Http\Controllers\AspectController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\DivisionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('divisions', DivisionController::class);
Route::resource('borrowers', BorrowerController::class);
Route::resource('aspects',AspectController::class);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
