<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 打刻画面を表示
     */
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // ステータスを判定
        $status = null;
        $currentBreak = null;

        if ($attendance) {
            if ($attendance->clock_out) {
                $status = 'finished';
            } elseif ($attendance->status === 'on_break') {
                $status = 'on_break';
                $currentBreak = $attendance->breaks()->whereNull('break_end')->first();
            } elseif ($attendance->clock_in) {
                $status = 'working';
            }
        }

        return view('attendance.index', compact('attendance', 'status', 'currentBreak'));
    }

    /**
     * 出勤打刻
     */
    public function clockIn(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        // 既に出勤打刻されているかチェック
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return redirect('/attendance')->with('error', '既に出勤打刻されています');
        }

        // 出勤打刻を作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => now(),
            'status' => 'working',
        ]);

        return redirect('/attendance')->with('success', '出勤打刻を記録しました');
    }

    /**
     * 退勤打刻
     */
    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (! $attendance) {
            return redirect('/attendance')->with('error', '出勤打刻が記録されていません');
        }

        // 休憩中でないかチェック
        if ($attendance->status === 'on_break') {
            return redirect('/attendance')->with('error', '休憩中は退勤できません');
        }

        // 退勤打刻を記録
        $attendance->update([
            'clock_out' => now(),
            'status' => 'finished',
        ]);

        return redirect('/attendance')->with('success', 'お疲れ様でした。');
    }

    /**
     * 休憩開始
     */
    public function breakStart(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (! $attendance) {
            return redirect('/attendance')->with('error', '出勤打刻が記録されていません');
        }

        // 既に休憩中でないかチェック
        if ($attendance->status === 'on_break') {
            return redirect('/attendance')->with('error', '既に休憩中です');
        }

        // 休憩開始を記録
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $attendance->update(['status' => 'on_break']);

        return redirect('/attendance')->with('success', '休憩開始を記録しました');
    }

    /**
     * 休憩終了
     */
    public function breakEnd(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (! $attendance) {
            return redirect('/attendance')->with('error', '出勤打刻が記録されていません');
        }

        // 休憩中かチェック
        if ($attendance->status !== 'on_break') {
            return redirect('/attendance')->with('error', '休憩中ではありません');
        }

        // 終了していない休憩を取得
        $currentBreak = $attendance->breaks()
            ->whereNull('break_end')
            ->first();

        if (! $currentBreak) {
            return redirect('/attendance')->with('error', '休憩開始が記録されていません');
        }

        // 休憩終了を記録
        $currentBreak->update(['break_end' => now()]);
        $attendance->update(['status' => 'working']);

        return redirect('/attendance')->with('success', '休憩終了を記録しました');
    }
}
