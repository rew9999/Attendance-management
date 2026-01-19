<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_displays_logged_in_user_name()
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'role' => 'employee',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get("/attendance/data/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_displays_selected_date()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $date = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get("/attendance/data/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($date->format('Y年'));
        $response->assertSee($date->format('n月j日'));
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_clock_in_out_times()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $clockIn = Carbon::now()->setTime(9, 0, 0);
        $clockOut = Carbon::now()->setTime(18, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $response = $this->actingAs($user)->get("/attendance/data/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($clockIn->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_break_times()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $breakStart = Carbon::now()->setTime(12, 0, 0);
        $breakEnd = Carbon::now()->setTime(13, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        $response = $this->actingAs($user)->get("/attendance/data/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($breakStart->format('H:i'));
        $response->assertSee($breakEnd->format('H:i'));
    }
}
