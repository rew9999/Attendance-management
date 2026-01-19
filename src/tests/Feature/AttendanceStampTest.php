<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        $response = $this->actingAs($user)->post('/attendance/clock-in');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_clock_in_can_only_be_done_once_per_day()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => Carbon::now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_displayed_in_attendance_list()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $clockInTime = Carbon::now()->setTime(9, 0, 0);
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($clockInTime->format('H:i'));
    }

    /**
     * 休憩ボタンが正しく機能する
     */
    public function test_break_start_button_works_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->actingAs($user)->post('/attendance/break-start');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * 休憩は一日に何回でもできる
     */
    public function test_break_can_be_taken_multiple_times()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(3),
        ]);

        // 1回目の休憩
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subHours(2),
            'break_end' => Carbon::now()->subHours(1)->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /**
     * 休憩戻ボタンが正しく機能する
     */
    public function test_break_end_button_works_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'status' => 'on_break',
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        $response = $this->actingAs($user)->post('/attendance/break-end');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('breaks', [
            'id' => $break->id,
        ]);

        $break->refresh();
        $this->assertNotNull($break->break_end);
    }

    /**
     * 休憩戻は一日に何回でもできる
     */
    public function test_break_end_can_be_done_multiple_times()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(3),
            'status' => 'on_break',
        ]);

        // 1回目の休憩完了
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subHours(2),
            'break_end' => Carbon::now()->subHours(1)->subMinutes(30),
        ]);

        // 2回目の休憩開始
        $break2 = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(10),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function test_break_time_is_displayed_in_attendance_list()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $breakStart = Carbon::now()->setTime(12, 0, 0);
        $breakEnd = Carbon::now()->setTime(13, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('1:00');
    }

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_works_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $response = $this->actingAs($user)->post('/attendance/clock-out');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
        ]);

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $clockInTime = Carbon::now()->setTime(9, 0, 0);
        $clockOutTime = Carbon::now()->setTime(18, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($clockOutTime->format('H:i'));
    }
}
