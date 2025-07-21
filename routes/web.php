
<?php

use App\Http\Controllers\AspectController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::resource('divisions', DivisionController::class);
Route::resource('borrowers', BorrowerController::class);
Route::resource('templates', TemplateController::class);
Route::resource('aspects',AspectController::class);
Route::resource('roles', RoleController::class);
Route::resource('users', UserController::class);
Route::resource('periods', PeriodController::class);

Route::post('periods/{period}/start', [PeriodController::class, 'start'])->name('periods.start');
Route::post('periods/{period}/end', [PeriodController::class,'stop'])->name('periods.stop');
Route::post('periods/check-expired', [PeriodController::class, 'checkExpiredPeriods'])->name('periods.check-expired');
Route::post('periods/{period}/extend',[PeriodController::class,'extend'])->name('periods.extend');

Route::middleware(['auth', 'check.active.period'])->group(function () {
    Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
    Route::post('/forms/save-step', [FormController::class, 'saveStepData'])->name('forms.save-step');
    Route::post('/forms/submit', [FormController::class, 'submitAll'])->name('forms.submit');
});

Route::get('summary', [SummaryController::class, 'show'])->name('summary');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
