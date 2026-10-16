<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentCourseController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('student.dashboard');
    }
    return redirect()->route('login');
});

// Guest / Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/demo-switch/{user}', [AuthController::class, 'demoSwitch'])->name('demo.switch');

// Authenticated Student & Shared Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [StudentCourseController::class, 'dashboard'])->name('student.dashboard');
    Route::post('/courses/{course}/enroll', [StudentCourseController::class, 'enroll'])->name('student.courses.enroll');
    Route::get('/courses/{course}', [StudentCourseController::class, 'showCourse'])->name('student.courses.show');
    Route::get('/courses/{course}/modules/{module}', [StudentCourseController::class, 'showCourse'])->name('student.courses.module');
    Route::post('/courses/{course}/modules/{module}/heartbeat', [StudentCourseController::class, 'heartbeat'])->name('student.modules.heartbeat');
    Route::get('/attachments/{attachment}/download', [StudentCourseController::class, 'downloadAttachment'])->name('student.attachments.download');
    Route::get('/enrollments/{enrollment}/certificate', [StudentCourseController::class, 'downloadCertificate'])->name('student.enrollments.certificate');
});

// Admin Panel Routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Courses & Modules
    Route::post('/courses', [AdminDashboardController::class, 'storeCourse'])->name('courses.store');
    Route::get('/courses/{course}', [AdminDashboardController::class, 'showCourse'])->name('courses.show');
    Route::put('/courses/{course}', [AdminDashboardController::class, 'updateCourse'])->name('courses.update');
    Route::post('/courses/{course}/modules', [AdminDashboardController::class, 'storeModule'])->name('courses.modules.store');
    Route::post('/modules/{module}/attachments', [AdminDashboardController::class, 'storeAttachment'])->name('modules.attachments.store');

    // Users & Enrollments & Zoom Sync
    Route::get('/users', [AdminDashboardController::class, 'usersIndex'])->name('users.index');
    Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
    Route::post('/users/import-csv', [AdminDashboardController::class, 'importUsersCsv'])->name('users.import-csv');
    Route::post('/users/{user}/approve', [AdminDashboardController::class, 'approveUser'])->name('users.approve');
    Route::post('/enrollments/assign', [AdminDashboardController::class, 'assignCourse'])->name('enrollments.assign');
    Route::put('/enrollments/{enrollment}/sync-hours', [AdminDashboardController::class, 'updateSyncHours'])->name('enrollments.sync-hours');
    Route::post('/courses/{course}/import-zoom-csv', [AdminDashboardController::class, 'importZoomCsv'])->name('courses.import-zoom-csv');

    // Audit Logs & Ministerial Exports
    Route::get('/logs', [AdminDashboardController::class, 'logsIndex'])->name('logs.index');
    Route::get('/courses/{course}/export-register-pdf', [AdminDashboardController::class, 'exportCourseRegisterPdf'])->name('courses.export-pdf');
    Route::get('/courses/{course}/export-register-csv', [AdminDashboardController::class, 'exportCourseRegisterCsv'])->name('courses.export-csv');
    Route::get('/enrollments/{enrollment}/dossier-pdf', [AdminDashboardController::class, 'exportStudentDossierPdf'])->name('enrollments.dossier-pdf');

    // White-Label Settings
    Route::get('/settings', [AdminDashboardController::class, 'settingsIndex'])->name('settings.index');
    Route::post('/settings', [AdminDashboardController::class, 'updateSettings'])->name('settings.update');
});
