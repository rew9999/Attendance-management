<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;
use Illuminate\Http\Request;

class CorrectionRequestController extends Controller
{
    /**
     * 勤怠修正申請一覧を表示
     */
    public function index()
    {
        $user = auth()->user();

        $requests = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('employee.correction.index', compact('requests'));
    }

    /**
     * 勤怠修正申請フォームを表示
     */
    public function create(Request $request)
    {
        $attendanceId = $request->query('attendance_id');

        if (!$attendanceId) {
            return redirect('/attendance/list')->with('error', '勤怠データを選択してください');
        }

        $attendance = Attendance::with('breaks')
            ->where('id', $attendanceId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attendance) {
            return redirect('/attendance/list')->with('error', '勤怠データが見つかりません');
        }

        return view('employee.correction.create', compact('attendance'));
    }

    /**
     * 勤怠修正申請を作成
     */
    public function store(AttendanceCorrectionStoreRequest $request, $attendanceId)
    {
        $attendance = Attendance::where('id', $attendanceId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attendance) {
            return redirect('/attendance/list')->with('error', '勤怠データが見つかりません');
        }

        // 既に承認待ちの申請があるかチェック
        $existingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->exists();

        if ($existingRequest) {
            return redirect()->back()->with('error', '既に申請中の修正依頼があります');
        }

        // 勤怠修正申請を作成
        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendanceId,
            'requested_clock_in' => $request->requested_clock_in,
            'requested_clock_out' => $request->requested_clock_out,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        // 休憩時間の修正申請があれば作成
        if ($request->has('breaks')) {
            foreach ($request->breaks as $break) {
                if (!empty($break['requested_break_start']) && !empty($break['requested_break_end'])) {
                    BreakCorrectionRequest::create([
                        'correction_request_id' => $correctionRequest->id,
                        'break_id' => $break['break_id'] ?? null,
                        'requested_break_start' => $break['requested_break_start'],
                        'requested_break_end' => $break['requested_break_end'],
                    ]);
                }
            }
        }

        return redirect('/attendance/list')->with('success', '修正申請を送信しました');
    }

    /**
     * 勤怠修正申請の詳細を表示
     */
    public function show($id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with(['attendance.breaks', 'breakCorrections'])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('employee.correction.show', compact('correctionRequest'));
    }
}
