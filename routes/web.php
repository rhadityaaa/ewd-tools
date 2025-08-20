
<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ApprovalReportController;
use App\Http\Controllers\AspectController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\MonitoringNoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ReportController; // Tambahkan ini
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowController;
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

// Tambahkan route untuk reports
Route::resource('reports', ReportController::class)->only(['index', 'show', 'destroy']);

// Routes untuk edit laporan yang ditolak (khusus unit bisnis)
Route::middleware(['auth'])->group(function () {
    Route::get('reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::post('reports/{report}/resubmit', [ReportController::class, 'resubmit'])->name('reports.resubmit');
});

// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
});

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
Route::patch('summary/{reportId}', [SummaryController::class, 'update'])->name('summary.update');

Route::prefix('watchlist')->name('naw.')->group(function () {
    Route::get('/', [MonitoringNoteController::class, 'show'])->name('show');
    Route::patch('/{monitoringNote}', [MonitoringNoteController::class, 'update'])->name('update');
    Route::post('/{monitoringNote}/action-items', [MonitoringNoteController::class, 'storeActionItem'])->name('action-items.store');
    Route::patch('/action-items/{actionItem}', [MonitoringNoteController::class, 'updateActionItem'])->name('action-items.update');
    Route::delete('/action-items/{actionItem}', [MonitoringNoteController::class, 'destroyActionItem'])->name('action-items.destroy');
    Route::post('/{monitoringNote}/copy-previous', [MonitoringNoteController::class, 'copyFromPrevious'])->name('copy-previous');
    Route::patch('/{monitoringNote}/watchlist-status', [MonitoringNoteController::class, 'updateWatchlistStatus'])->name('watchlist-status');
    Route::post('/{monitoringNote}/submit', [MonitoringNoteController::class, 'submitNAW'])->name('submit'); // Tambah route ini
    Route::get('/check-access', [MonitoringNoteController::class, 'checkAccess'])->name('check-access');
});

Route::prefix('api/dashboard')->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/recent-watchlist', [DashboardController::class, 'getRecentWatchlist']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Role-based route protection examples
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('borrowers', BorrowerController::class);
});

Route::middleware(['auth', 'permission:create_reports'])->group(function () {
    Route::post('/forms/submit', [FormController::class, 'submitAll'])->name('forms.submit');
});

Route::middleware(['auth', 'permission:approve_reports'])->group(function () {
    Route::patch('summary/{reportId}', [SummaryController::class, 'update'])->name('summary.update');
});

// Approval workflow routes (different from approval reports)
Route::middleware(['auth'])->group(function () {
    Route::prefix('approvals')->name('approvals.')->group(function () {
        // All users with approval permissions can view
        Route::middleware(['role:risk_analyst,kadept_bisnis,kadept_risk,super_admin'])->group(function () {
            Route::get('/', [ApprovalController::class, 'index'])->name('index');
            Route::get('/{report}', [ApprovalController::class, 'show'])->name('show');
            Route::post('/{report}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('/{report}/reject', [ApprovalController::class, 'reject'])->name('reject');
            Route::post('/{report}/revision', [ApprovalController::class, 'requestRevision'])->name('revision');
        });
        
        // Only department heads can override
        Route::middleware(['role:kadept_bisnis,kadept_risk,super_admin'])->group(function () {
            Route::post('/{report}/override', [ApprovalController::class, 'override'])->name('override');
        });
    });
});

// Approval Report Routes
Route::middleware(['auth'])->prefix('approval-reports')->name('approval-reports.')->group(function () {
    Route::get('/', [ApprovalReportController::class, 'index'])->name('index');
    Route::get('/summary', [ApprovalReportController::class, 'summary'])->name('summary');
    
    // ✅ Show route for detailed report view
    Route::get('/{report}', [ApprovalReportController::class, 'show'])
        ->middleware('role:kadept_bisnis|kadept_risk|super_admin')
        ->name('show');
    
    Route::get('/{reportId}/workflow', [ApprovalReportController::class, 'workflow'])->name('workflow');
    
    // ✅ Update export route
    Route::get('/export/{period}', [ApprovalReportController::class, 'export'])
        ->middleware('role:kadept_bisnis|kadept_risk|super_admin')
        ->name('export');
    
    Route::put('reports/{report}/update-step', [ReportController::class, 'updateStep'])->name('reports.update-step');
        Route::put('reports/{report}/update-borrower', [ReportController::class, 'updateBorrower'])->name('reports.update-borrower');
        Route::put('reports/{report}/update-facility', [ReportController::class, 'updateFacility'])->name('reports.update-facility');
        Route::put('reports/{report}/update-aspect', [ReportController::class, 'updateAspect'])->name('reports.update-aspect');
});

// ✅ Workflow routes with proper naming
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/{report}', [WorkflowController::class, 'show'])->name('show');
        Route::post('/{report}/approve', [WorkflowController::class, 'approve'])->name('approve');
        Route::post('/{report}/reject', [WorkflowController::class, 'reject'])->name('reject');
        Route::post('/{report}/resubmit', [WorkflowController::class, 'resubmit'])->name('resubmit');
    });
});
