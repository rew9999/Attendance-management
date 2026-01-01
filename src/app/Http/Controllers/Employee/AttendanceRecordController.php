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

        $query = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc');

        // 日付範囲フィルター
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // 月フィルター
        if ($request->filled('month')) {
            $month = Carbon::parse($request->month);
            $query->whereYear('date', $month->year)
                  ->whereMonth('date', $month->month);
        }

        $attendances = $query->paginate(31);

        return view('employee.attendance.index', compact('attendances'));
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
