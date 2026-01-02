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
