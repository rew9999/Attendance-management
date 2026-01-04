<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyAttendanceController extends Controller
{
    /**
     * 日次勤怠一覧を表示
     */
    public function index(Request $request)
    {
        // デフォルトは今日の日付
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $dateString = $date->toDateString();

        // 名前検索
        $nameQuery = $request->filled('name') ? $request->name : null;

        // 勤怠データを取得
        $query = Attendance::with(['user', 'breaks'])
            ->where('date', $dateString)
            ->whereHas('user', function($q) {
                $q->where('role', 'employee');
            });

        if ($nameQuery) {
            $query->whereHas('user', function($q) use ($nameQuery) {
                $q->where('name', 'like', '%' . $nameQuery . '%');
            });
        }

        $attendances = $query->orderBy('clock_in', 'asc')->paginate(20);

        // 勤怠データがないスタッフも表示する場合
        $employeesWithoutAttendance = collect();

        if (!$nameQuery || $request->filled('show_all')) {
            $attendedUserIds = $attendances->pluck('user_id')->toArray();

            $employeesQuery = User::where('role', 'employee')
                ->whereNotIn('id', $attendedUserIds);

            if ($nameQuery) {
                $employeesQuery->where('name', 'like', '%' . $nameQuery . '%');
            }

            $employeesWithoutAttendance = $employeesQuery->get();
        }

        return view('admin.daily-attendance.index', compact('attendances', 'employeesWithoutAttendance', 'date'));
    }

    /**
     * 勤怠詳細を表示
     */
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks'])
            ->findOrFail($id);

        return view('admin.daily-attendance.show', compact('attendance'));
    }

    /**
     * 勤怠詳細を更新
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        // バリデーション
        $request->validate([
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // 出勤・退勤時刻を更新
        $date = $attendance->date;

        $attendance->update([
            'clock_in' => $request->clock_in ? Carbon::parse($date . ' ' . $request->clock_in) : null,
            'clock_out' => $request->clock_out ? Carbon::parse($date . ' ' . $request->clock_out) : null,
            'remarks' => $request->remarks,
        ]);

        // 休憩時間を更新
        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakId => $breakData) {
                $break = $attendance->breaks()->find($breakId);

                if ($break) {
                    $break->update([
                        'break_start' => isset($breakData['break_start']) ? Carbon::parse($date . ' ' . $breakData['break_start']) : null,
                        'break_end' => isset($breakData['break_end']) ? Carbon::parse($date . ' ' . $breakData['break_end']) : null,
                    ]);
                }
            }
        }

        return redirect()->route('admin.attendance.date', $id)->with('success', '勤怠情報を更新しました');
    }

    /**
     * 日次勤怠をCSVエクスポート
     */
    public function export(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : Carbon::today()->toDateString();

        $attendances = Attendance::with(['user', 'breaks'])
            ->where('date', $date)
            ->whereHas('user', function($q) {
                $q->where('role', 'employee');
            })
            ->orderBy('clock_in', 'asc')
            ->get();

        $filename = sprintf(
            '日次勤怠_%s.csv',
            Carbon::parse($date)->format('Ymd')
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($attendances) {
            $stream = fopen('php://output', 'w');

            // BOMを追加（Excel対応）
            fprintf($stream, chr(0xEF).chr(0xBB).chr(0xBF));

            // ヘッダー行
            fputcsv($stream, ['氏名', '出勤時刻', '退勤時刻', '休憩時間', '勤務時間']);

            // データ行
            foreach ($attendances as $attendance) {
                $totalBreakMinutes = $attendance->getTotalBreakMinutes();
                $workMinutes = $attendance->getWorkMinutes();

                fputcsv($stream, [
                    $attendance->user->name,
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $totalBreakMinutes ? sprintf('%d分', $totalBreakMinutes) : '',
                    $workMinutes ? sprintf('%d分', $workMinutes) : '',
                ]);
            }

            fclose($stream);
        };

        return response()->stream($callback, 200, $headers);
    }
}
