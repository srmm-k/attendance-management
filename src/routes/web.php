<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Users\AttendanceController;
use App\Http\Controllers\Users\RequestController;
use App\Http\Controllers\Users\RequestController as UserRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\Admin\LogoutController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/
//メール認証関連のルートを明示的に定義
Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    // 既に認証済みの場合、verify-email画面に戻る
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('attendance.create');
    }

    //認証処理を実行
    if ($request->user()->markEmailAsVerified()) {
        event(new \Illuminate\Auth\Events\Verified($request->user()));
    }

    // 認証完了後、勤怠登録画面へリダイレクト
    return redirect()->route('attendance.create')->with('verified', true);
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware(['auth'])->name('verification.notice');

// 一般ユーザーのログインルート
Route::get('/login', [CustomLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [CustomLoginController::class, 'login']);

// 一般ユーザー向けの認証済みルート
Route::middleware(['auth:web', 'verified'])->group(function () {

    // 勤怠関連
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'create'])->name('create');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/list', [AttendanceController::class, 'index'])->name('list');
    });

    // 申請関連
        Route::post('/stamp_correction_request', [UserRequestController::class, 'store'])->name('applications.store');

});


// 認証済みであればアクセス可能とし、Controller内で is_admin で処理を振り分ける
Route::middleware(['auth:web'])->group(function () {

    // 申請一覧（一般も管理者も同じパスを使用）
    Route::get('/stamp_correction_request/list', [UserRequestController::class, 'index'])->name('applications.index');

    //修正申請画面
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRequestController::class, 'show'])->name('admin.requests.detail');
    Route::put('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRequestController::class, 'update'])->name('admin.requests.update');

    //勤怠詳細画面（一般・管理者共通パス）
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
        Route::put('/{id}', [AttendanceController::class, 'update'])->name('update');
    });
});


// 管理者のログインルート
Route::get('/admin/login', [CustomLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [CustomLoginController::class, 'login']);

// 管理者のログアウト機能
Route::post('/admin/logout', [LogoutController::class, 'destroy'])->name('admin.logout');

// 管理者向けの認証済みルート
Route::prefix('admin')->name('admin.')->middleware(['auth:web', 'is_admin'])->group(function () {
    // 勤怠関連
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendances.list');

    // スタッフ関連
    Route::get('/staff/list', [AdminUserController::class, 'index'])->name('users');
    Route::get('/attendance/staff/{id}', [AdminUserController::class, 'showAttendances'])->name('users.attendances');
    Route::get('/attendance/staff/{id}/export-csv', [AdminUserController::class, 'exportCsv'])->name('users.attendances.exportCsv');
});
