<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\AttendanceController;
use App\Http\Controllers\Users\RequestController as UserRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// 一般ユーザー向けの認証済みルート
Route::middleware(['auth:web'])->group(function () {
    // 勤怠関連
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    Route::put('/attendance/detail/{id}', [AttendanceController::class, 'update'])->name('attendance.update');

    // 申請関連
    Route::get('/stamp_correction_request/list', [UserRequestController::class, 'index'])->name('applications.index');
    Route::post('/stamp_correction_request', [UserRequestController::class, 'store'])->name('applications.store');
});

// 管理者用ログイン
Route::prefix('admin')->name('admin.')->middleware(['guest'])->group(function () {
    Route::get('/login', [Fortify\Http\Controllers\AuthenticatedSessionController::class, 'create'])->name('login');
});

// 管理者向けの認証済みルート
Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'is_admin'])->group(function () {
    // 勤怠関連
    Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances');
    Route::get('/attendances/{id}', [AdminAttendanceController::class, 'show'])->name('attendances.detail');
    Route::put('/attendances/{id}', [AdminAttendanceController::class, 'update'])->name('attendances.update');

    // スタッフ関連
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::get('/users/{user}/attendances', [AdminUserController::class, 'showAttendances'])->name('users.attendances');
    Route::get('/users/{user}/attendances/export-csv', [AdminUserController::class, 'exportCsv'])->name('users.attendances.exportCsv'); // ★追加

    // 申請関連
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests');
    Route::get('/requests/{id}', [AdminRequestController::class, 'show'])->name('requests.detail');
    Route::put('/requests/{id}', [AdminRequestController::class, 'update'])->name('requests.update');
});