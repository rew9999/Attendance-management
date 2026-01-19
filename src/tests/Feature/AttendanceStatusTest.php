<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_datetime_is_displayed_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 日付と時刻が表示されているか確認（画面上に表示されるフォーマットで確認）
        $response->assertSee(Carbon::now()->format('Y年'));
        $response->assertSee(Carbon::now()->format('n月'));
        $response->assertSee(Carbon::now()->format('j日'));
    }

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_displays_off_duty_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_displays_working_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHour(),
            'status' => 'working',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_displays_on_break_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'status' => 'on_break',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_displays_clocked_out_correctly()
    {
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now()->subHour(),
            'status' => 'finished',
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
