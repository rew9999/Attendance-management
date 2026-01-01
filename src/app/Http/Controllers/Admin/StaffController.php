<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * スタッフ一覧を表示
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'employee');

        // 名前で検索
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $staff = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.staff.index', compact('staff'));
    }

    /**
     * 個別スタッフの勤怠一覧を表示
     */
    public function show(Request $request, $id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        $query = Attendance::with('breaks')
            ->where('user_id', $id)
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

        return view('admin.staff.show', compact('user', 'attendances'));
    }

    /**
     * スタッフの勤怠をCSVエクスポート
     */
    public function export(Request $request, $id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        $query = Attendance::with('breaks')->where('user_id', $id);

        // 日付範囲フィルター
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $attendances = $query->orderBy('date', 'asc')->get();

        $filename = sprintf(
            '%s_勤怠記録_%s.csv',
            $user->name,
            now()->format('Ymd')
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
            fputcsv($stream, ['日付', '出勤時刻', '退勤時刻', '休憩時間', '勤務時間']);

            // データ行
            foreach ($attendances as $attendance) {
                $totalBreakMinutes = $attendance->getTotalBreakMinutes();
                $workMinutes = $attendance->getWorkMinutes();

                fputcsv($stream, [
                    $attendance->date,
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
