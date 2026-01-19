<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    /**
     * 自分の勤怠記録一覧を表示
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // 月の指定（デフォルトは今月）
        $currentMonth = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        // 当月の開始日と終了日
        $startDate = $currentMonth->copy()->startOfMonth();
        $endDate = $currentMonth->copy()->endOfMonth();

        // 当月の全日付を生成
        $allDates = [];
        $date = $startDate->copy();
        while ($date <= $endDate) {
            $allDates[$date->toDateString()] = null;
            $date->addDay();
        }

        // 当月の勤怠データを取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        // 前月・翌月の日付
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        return view('employee.attendance.index', compact('attendances', 'allDates', 'currentMonth', 'prevMonth', 'nextMonth'));
    }

    /**
     * 勤怠記録の詳細を表示
     */
    public function show($id)
    {
        $attendance = Attendance::with('breaks')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('employee.attendance.show', compact('attendance'));
    }
}
