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
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'resetPasswordWithIdentity'])->name('password.reset.submit');
Route::post('/forgot-password/email', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.token');
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithToken'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/demo-switch/{user}', [AuthController::class, 'demoSwitch'])->name('demo.switch');
Route::post('/webhooks/zoom', [\App\Http\Controllers\ZoomWebhookController::class, 'handle'])->name('webhooks.zoom');

// Authenticated Student & Shared Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    Route::get('/dashboard', [StudentCourseController::class, 'dashboard'])->name('student.dashboard');
    Route::post('/courses/redeem-code', [StudentCourseController::class, 'redeemCode'])->name('student.courses.redeem-code');
    Route::post('/courses/{course}/enroll', [StudentCourseController::class, 'enroll'])->name('student.courses.enroll');
    Route::get('/courses/{course}', [StudentCourseController::class, 'showCourse'])->name('student.courses.show');
    Route::get('/courses/{course}/modules/{module}', [StudentCourseController::class, 'showCourse'])->name('student.courses.module');
    Route::post('/courses/{course}/modules/{module}/heartbeat', [StudentCourseController::class, 'heartbeat'])->name('student.modules.heartbeat');
    Route::post('/courses/{course}/modules/{module}/zoom-join', [StudentCourseController::class, 'joinZoomModule'])->name('student.modules.zoom-join');
    Route::get('/attachments/{attachment}/download', [StudentCourseController::class, 'downloadAttachment'])->name('student.attachments.download');
    Route::get('/enrollments/{enrollment}/certificate', [StudentCourseController::class, 'downloadCertificate'])->name('student.enrollments.certificate');
    Route::get('/enrollments/{enrollment}/certificate-preview', [StudentCourseController::class, 'previewCertificate'])->name('student.enrollments.certificate-preview');
    Route::get('/enrollments/{enrollment}/audit-pdf', [StudentCourseController::class, 'downloadPersonalAuditPdf'])->name('student.enrollments.audit-pdf');
});

// Admin Panel Routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Courses Management
    Route::get('/courses', [\App\Http\Controllers\AdminCourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [AdminDashboardController::class, 'storeCourse'])->name('courses.store');
    Route::get('/courses/{course}', [AdminDashboardController::class, 'showCourse'])->name('courses.show');
    Route::put('/courses/{course}', [AdminDashboardController::class, 'updateCourse'])->name('courses.update');
    Route::post('/courses/{course}/duplicate', [AdminDashboardController::class, 'duplicateCourse'])->name('courses.duplicate');
    Route::patch('/courses/{course}/toggle-status', [AdminDashboardController::class, 'toggleCourseStatus'])->name('courses.toggle-status');
    Route::delete('/courses/{course}', [AdminDashboardController::class, 'destroyCourse'])->name('courses.destroy');

    Route::post('/courses/{course}/modules', [AdminDashboardController::class, 'storeModule'])->name('courses.modules.store');
    Route::post('/courses/{course}/modules/reorder', [AdminDashboardController::class, 'reorderModules'])->name('courses.modules.reorder');
    Route::put('/modules/{module}', [AdminDashboardController::class, 'updateModule'])->name('modules.update');
    Route::post('/modules/{module}/sync-zoom', [AdminDashboardController::class, 'syncModuleZoomAttendance'])->name('modules.sync-zoom');
    Route::post('/courses/{course}/sync-zoom-meeting', [AdminDashboardController::class, 'syncCourseZoomMeeting'])->name('courses.sync-zoom-meeting');
    Route::post('/modules/{module}/duplicate', [AdminDashboardController::class, 'duplicateModule'])->name('modules.duplicate');
    Route::patch('/modules/{module}/toggle-status', [AdminDashboardController::class, 'toggleModuleStatus'])->name('modules.toggle-status');
    Route::delete('/modules/{module}', [AdminDashboardController::class, 'destroyModule'])->name('modules.destroy');

    Route::post('/modules/{module}/attachments', [AdminDashboardController::class, 'storeAttachment'])->name('modules.attachments.store');
    Route::delete('/attachments/{attachment}', [AdminDashboardController::class, 'destroyAttachment'])->name('attachments.destroy');

    // Users, Enrollments & Zoom Sync
    Route::get('/users', [AdminDashboardController::class, 'usersIndex'])->name('users.index');
    Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/import-csv', [AdminDashboardController::class, 'importUsersCsv'])->name('users.import-csv');
    Route::post('/users/{user}/approve', [AdminDashboardController::class, 'approveUser'])->name('users.approve');

    Route::post('/enrollments/assign', [AdminDashboardController::class, 'assignCourse'])->name('enrollments.assign');
    Route::put('/enrollments/{enrollment}/sync-hours', [AdminDashboardController::class, 'updateSyncHours'])->name('enrollments.sync-hours');
    Route::post('/enrollments/{enrollment}/reset', [AdminDashboardController::class, 'resetEnrollmentProgress'])->name('enrollments.reset');
    Route::post('/enrollments/{enrollment}/modules/{module}/reset', [AdminDashboardController::class, 'resetModuleProgress'])->name('enrollments.modules.reset');
    Route::delete('/enrollments/{enrollment}', [AdminDashboardController::class, 'destroyEnrollment'])->name('enrollments.destroy');
    Route::post('/courses/{course}/import-zoom-csv', [AdminDashboardController::class, 'importZoomCsv'])->name('courses.import-zoom-csv');

    // Audit Logs & Ministerial Exports
    Route::get('/logs', [AdminDashboardController::class, 'logsIndex'])->name('logs.index');
    Route::get('/logs/export-pdf', [AdminDashboardController::class, 'exportLogsPdf'])->name('logs.export-pdf');
    Route::get('/courses/{course}/export-register-pdf', [AdminDashboardController::class, 'exportCourseRegisterPdf'])->name('courses.export-pdf');
    Route::get('/courses/{course}/export-dossier-pdf', [AdminDashboardController::class, 'exportCourseDossierPdf'])->name('courses.export-dossier-pdf');
    Route::get('/courses/{course}/export-register-csv', [AdminDashboardController::class, 'exportCourseRegisterCsv'])->name('courses.export-csv');
    Route::get('/enrollments/{enrollment}/dossier-pdf', [AdminDashboardController::class, 'exportStudentDossierPdf'])->name('enrollments.dossier-pdf');

    // Email Logs, Configuration & Testing
    Route::get('/emails', [\App\Http\Controllers\AdminEmailController::class, 'index'])->name('emails.index');
    Route::post('/emails/settings', [\App\Http\Controllers\AdminEmailController::class, 'updateEmailSettings'])->name('emails.settings.update');
    Route::get('/emails/{email}/preview', [\App\Http\Controllers\AdminEmailController::class, 'preview'])->name('emails.preview');
    Route::post('/emails/send-test', [\App\Http\Controllers\AdminEmailController::class, 'sendTest'])->name('emails.send-test');
    Route::delete('/emails/{email}', [\App\Http\Controllers\AdminEmailController::class, 'destroy'])->name('emails.destroy');
    Route::post('/emails/clear', [\App\Http\Controllers\AdminEmailController::class, 'clear'])->name('emails.clear');

    // White-Label Settings
    Route::get('/settings', [AdminDashboardController::class, 'settingsIndex'])->name('settings.index');
    Route::post('/settings', [AdminDashboardController::class, 'updateSettings'])->name('settings.update');

    // Media Library (Badges, Videos, Attachments)
    Route::get('/media', [\App\Http\Controllers\AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media', [\App\Http\Controllers\AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [\App\Http\Controllers\AdminMediaController::class, 'destroy'])->name('media.destroy');
});
