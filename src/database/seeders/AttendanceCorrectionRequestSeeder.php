<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 一般ユーザーを取得
        $employees = User::where('role', 'employee')->get();

        if ($employees->isEmpty()) {
            $this->command->warn('一般ユーザーが存在しません。先にEmployeeUserSeederを実行してください。');
            return;
        }

        // 各ユーザーの勤怠データから申請を作成
        foreach ($employees->take(3) as $employee) {
            // ユーザーの勤怠データを取得
            $attendances = Attendance::where('user_id', $employee->id)
                ->orderBy('date', 'desc')
                ->take(2)
                ->get();

            foreach ($attendances as $index => $attendance) {
                // 承認待ちと承認済みの申請を作成
                $status = $index === 0 ? 'pending' : 'approved';

                // 修正後の時刻を設定
                $requestedClockIn = $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->subMinutes(rand(5, 15))
                    : null;

                $requestedClockOut = $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->addMinutes(rand(5, 15))
                    : null;

                $correctionRequest = AttendanceCorrectionRequest::create([
                    'user_id' => $employee->id,
                    'attendance_id' => $attendance->id,
                    'requested_clock_in' => $requestedClockIn,
                    'requested_clock_out' => $requestedClockOut,
                    'reason' => '遅延のため',
                    'status' => $status,
                    'approved_at' => $status === 'approved' ? now() : null,
                    'approved_by' => $status === 'approved' ? 1 : null,
                    'created_at' => now()->subDays($index),
                ]);

                // 休憩時間の修正申請を作成
                if ($attendance->breaks->isNotEmpty()) {
                    foreach ($attendance->breaks->take(1) as $break) {
                        BreakCorrectionRequest::create([
                            'correction_request_id' => $correctionRequest->id,
                            'break_id' => $break->id,
                            'requested_break_start' => $break->break_start
                                ? Carbon::parse($break->break_start)->addMinutes(5)
                                : null,
                            'requested_break_end' => $break->break_end
                                ? Carbon::parse($break->break_end)->subMinutes(5)
                                : null,
                        ]);
                    }
                }
            }
        }

        $this->command->info('申請ダミーデータを作成しました。');
    }
}
