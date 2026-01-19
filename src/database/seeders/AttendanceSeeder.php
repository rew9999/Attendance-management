<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
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

        // 今日から過去7日分のダミーデータを作成
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays($i)->toDateString();

            foreach ($employees as $employee) {
                // 80%の確率で出勤データを作成
                if (rand(1, 100) <= 80) {
                    // 出勤時刻: 8:30〜9:30のランダム
                    $clockInHour = 9;
                    $clockInMinute = rand(0, 60);
                    if ($clockInMinute >= 60) {
                        $clockInHour = 10;
                        $clockInMinute = 0;
                    }

                    $clockIn = Carbon::parse($date)->setTime($clockInHour, $clockInMinute);

                    // 退勤時刻: 17:30〜19:00のランダム
                    $clockOutHour = rand(17, 18);
                    $clockOutMinute = rand(0, 59);
                    if ($clockOutHour == 18 && $clockOutMinute > 0) {
                        $clockOutMinute = rand(0, 30);
                    }

                    $clockOut = Carbon::parse($date)->setTime($clockOutHour, $clockOutMinute);

                    // 勤怠データ作成
                    $attendance = Attendance::create([
                        'user_id' => $employee->id,
                        'date' => $date,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => 'finished',
                    ]);

                    // 休憩時間を1〜2回作成
                    $breakCount = rand(1, 2);

                    for ($b = 0; $b < $breakCount; $b++) {
                        if ($b == 0) {
                            // 最初の休憩: 12:00〜13:00
                            $breakStart = Carbon::parse($date)->setTime(12, 0);
                            $breakEnd = Carbon::parse($date)->setTime(13, 0);
                        } else {
                            // 2回目の休憩: 15:00〜15:15
                            $breakStart = Carbon::parse($date)->setTime(15, 0);
                            $breakEnd = Carbon::parse($date)->setTime(15, 15);
                        }

                        AttendanceBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $breakStart,
                            'break_end' => $breakEnd,
                        ]);
                    }
                }
            }
        }

        $this->command->info('勤怠ダミーデータを作成しました。');
    }
}
