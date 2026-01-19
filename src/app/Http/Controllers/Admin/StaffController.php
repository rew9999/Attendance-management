<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
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
            $query->where('name', 'like', '%'.$request->name.'%');
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

        // 月フィルター（デフォルトは現在の月）
        $month = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        $query = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date', 'desc');

        $attendances = $query->paginate(31);

        return view('admin.staff.show', compact('user', 'attendances'));
    }

    /**
     * スタッフの勤怠をCSVエクスポート
     */
    public function export(Request $request, $id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        // 月フィルター（デフォルトは現在の月）
        $month = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        $query = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month);

        $attendances = $query->orderBy('date', 'asc')->get();

        $filename = sprintf(
            '%s_勤怠記録_%s.csv',
            $user->name,
            $month->format('Y年m月')
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($attendances) {
            $stream = fopen('php://output', 'w');

            // BOMを追加（Excel対応）
            fprintf($stream, chr(0xEF).chr(0xBB).chr(0xBF));

            // ヘッダー行
            fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

            // データ行
            foreach ($attendances as $attendance) {
                $date = Carbon::parse($attendance->date);
                $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];

                $totalBreakMinutes = $attendance->getTotalBreakMinutes();
                $breakHours = floor($totalBreakMinutes / 60);
                $breakMins = $totalBreakMinutes % 60;

                $workMinutes = $attendance->getWorkMinutes();
                $workHours = floor($workMinutes / 60);
                $workMins = $workMinutes % 60;

                fputcsv($stream, [
                    $date->format('m/d').'('.$dayOfWeek.')',
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-',
                    sprintf('%d:%02d', $breakHours, $breakMins),
                    sprintf('%d:%02d', $workHours, $workMins),
                ]);
            }

            fclose($stream);
        };

        return response()->stream($callback, 200, $headers);
    }
}
