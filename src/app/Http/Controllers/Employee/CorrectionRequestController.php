<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    /**
     * 勤怠修正申請一覧を表示
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status', 'pending');

        $query = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->with(['attendance', 'user']);

        if ($status === 'approved') {
            $query->whereIn('status', ['approved', 'rejected']);
        } else {
            $query->where('status', 'pending');
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('employee.correction.index', compact('requests', 'status'));
    }

    /**
     * 勤怠修正申請フォームを表示
     */
    public function create(Request $request)
    {
        $attendanceId = $request->query('attendance_id');

        if (! $attendanceId) {
            return redirect('/attendance/list')->with('error', '勤怠データを選択してください');
        }

        $attendance = Attendance::with('breaks')
            ->where('id', $attendanceId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $attendance) {
            return redirect('/attendance/list')->with('error', '勤怠データが見つかりません');
        }

        return view('employee.correction.create', compact('attendance'));
    }

    /**
     * 勤怠修正申請を作成
     */
    public function store(CorrectionRequestStoreRequest $request, $attendanceId)
    {
        $attendance = Attendance::where('id', $attendanceId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $attendance) {
            return redirect('/attendance/list')->with('error', '勤怠データが見つかりません');
        }

        // 既に承認待ちの申請があるかチェック
        $existingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            return redirect()->back()->with('error', '既に申請中の修正依頼があります');
        }

        // 日付を取得
        $date = \Carbon\Carbon::parse($attendance->date)->format('Y-m-d');

        // 時刻フォーマットを日時フォーマットに変換（フルの日時フォーマットの場合はそのまま使用）
        $requestedClockIn = $request->requested_clock_in;
        $requestedClockOut = $request->requested_clock_out;

        // 時刻のみの場合は日付を追加
        if ($requestedClockIn && ! str_contains($requestedClockIn, ' ')) {
            $requestedClockIn = $date.' '.$requestedClockIn;
        }
        if ($requestedClockOut && ! str_contains($requestedClockOut, ' ')) {
            $requestedClockOut = $date.' '.$requestedClockOut;
        }

        // 勤怠修正申請を作成
        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendanceId,
            'requested_clock_in' => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // 休憩時間の修正申請があれば作成
        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                $hasStart = ! empty($break['requested_break_start']);
                $hasEnd = ! empty($break['requested_break_end']);

                // 開始と終了の両方が入力されている場合のみ作成
                if ($hasStart && $hasEnd) {
                    $breakStartStr = $break['requested_break_start'];
                    $breakEndStr = $break['requested_break_end'];

                    if (! str_contains($breakStartStr, ' ')) {
                        $breakStartStr = $date.' '.$breakStartStr;
                    }
                    if (! str_contains($breakEndStr, ' ')) {
                        $breakEndStr = $date.' '.$breakEndStr;
                    }

                    BreakCorrectionRequest::create([
                        'correction_request_id' => $correctionRequest->id,
                        'break_id' => $break['break_id'] ?? null,
                        'requested_break_start' => $breakStartStr,
                        'requested_break_end' => $breakEndStr,
                    ]);
                }
                // 片方だけ入力されている場合はスキップ（バリデーションエラーにしない）
            }
        }

        return redirect()->route('employee.attendance.list')->with('success', '修正申請を送信しました');
    }

    /**
     * 勤怠修正申請の詳細を表示
     */
    public function show($id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with(['attendance.breaks', 'breakCorrectionRequests'])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('employee.correction.show', compact('correctionRequest'));
    }
}
