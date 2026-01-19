<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * 承認待ち申請一覧を表示
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $requests = AttendanceCorrectionRequest::with(['user', 'attendance'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.approval.index', compact('requests'));
    }

    /**
     * 申請詳細を表示
     */
    public function show($id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with([
            'user',
            'attendance.breaks',
            'breakCorrectionRequests',
        ])->findOrFail($id);

        return view('admin.approval.show', compact('correctionRequest'));
    }

    /**
     * 申請を承認
     */
    public function approve(Request $request, $id)
    {
        $correctionRequest = AttendanceCorrectionRequest::with(['attendance', 'breakCorrectionRequests'])
            ->findOrFail($id);

        if ($correctionRequest->status !== 'pending') {
            return redirect()->back()->with('error', '既に処理済みの申請です');
        }

        // 勤怠データを更新
        $attendance = $correctionRequest->attendance;

        if ($correctionRequest->requested_clock_in) {
            $attendance->clock_in = $correctionRequest->requested_clock_in;
        }

        if ($correctionRequest->requested_clock_out) {
            $attendance->clock_out = $correctionRequest->requested_clock_out;
        }

        $attendance->save();

        // 休憩時間の修正を適用
        foreach ($correctionRequest->breakCorrectionRequests as $breakCorrection) {
            if ($breakCorrection->break_id) {
                // 既存の休憩を更新
                $break = $attendance->breaks()->find($breakCorrection->break_id);
                if ($break) {
                    $break->update([
                        'break_start' => $breakCorrection->requested_break_start,
                        'break_end' => $breakCorrection->requested_break_end,
                    ]);
                }
            } else {
                // 新しい休憩を追加
                $attendance->breaks()->create([
                    'break_start' => $breakCorrection->requested_break_start,
                    'break_end' => $breakCorrection->requested_break_end,
                ]);
            }
        }

        // 申請ステータスを承認済みに更新
        $correctionRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Ajax リクエストの場合はJSONを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => '申請を承認しました']);
        }

        return redirect()->route('admin.stamp_correction_request.list')->with('success', '申請を承認しました');
    }
}
