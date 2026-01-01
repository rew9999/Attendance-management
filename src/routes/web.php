<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\DailyAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Employee\AttendanceRecordController;
use App\Http\Controllers\Employee\CorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// メール認証確認画面（一般ユーザー）
Route::get('/stamp_correction_request/list', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// メール認証処理
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

// 認証済みユーザー共通
Route::middleware(['auth', 'verified'])->group(function () {

    // 一般ユーザー（employee）
    Route::middleware('role.employee')->group(function () {

        // 打刻画面（一般ユーザー）
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
        Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

        // 勤怠一覧画面（一般ユーザー）
        Route::get('/attendance/list', [AttendanceRecordController::class, 'index'])->name('employee.attendance.list');

        // 勤怠詳細画面（一般ユーザー）
        Route::get('/attendance/data/{id}', [AttendanceRecordController::class, 'show'])->name('employee.attendance.data');

        // 勤怠修正申請画面（一般ユーザー）
        Route::get('/attendance/edit/request/{id}', [CorrectionRequestController::class, 'create'])->name('employee.attendance.edit.request');
        Route::post('/attendance/edit/request/{id}', [CorrectionRequestController::class, 'store'])->name('employee.attendance.edit.request.post');
    });

    // 管理者（admin）
    Route::middleware('role.admin')->prefix('admin')->name('admin.')->group(function () {

        // 勤怠一覧画面（管理者）
        Route::get('/attendance/list', [DailyAttendanceController::class, 'index'])->name('attendance.list');

        // 日別勤怠一覧画面（管理者）
        Route::get('/attendance/date/{id}', [DailyAttendanceController::class, 'show'])->name('attendance.date');

        // スタッフ一覧画面（管理者）
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');

        // スタッフ別勤怠一覧画面（管理者）
        Route::get('/attendance/staff/{id}', [StaffController::class, 'attendance'])->name('attendance.staff');

        // 申請一覧画面（管理者）
        Route::get('/stamp_correction_request/list', [ApprovalController::class, 'index'])->name('stamp_correction_request.list');

        // 修正承認画面詳細画面（管理者）
        Route::get('/stamp_correction_request/approve/attendance/{id}', [ApprovalController::class, 'show'])->name('stamp_correction_request.approve');

        // 修正承認処理
        Route::post('/stamp_correction_request/approve/attendance/{id}', [ApprovalController::class, 'approve'])->name('stamp_correction_request.approve.post');
    });
});
