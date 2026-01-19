<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_all_users_attendance_for_the_day_is_displayed()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'employee']);
        $user2 = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user1->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee($user2->name);
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_is_displayed_on_initial_load()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y年n月j日'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_button_displays_previous_day_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::yesterday(),
            'clock_in' => Carbon::yesterday()->setTime(9, 0, 0),
            'clock_out' => Carbon::yesterday()->setTime(18, 0, 0),
        ]);

        $previousDate = Carbon::yesterday()->format('Y-m-d');
        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$previousDate}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::yesterday()->format('Y年n月j日'));
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_day_button_displays_next_day_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::tomorrow(),
            'clock_in' => Carbon::tomorrow()->setTime(9, 0, 0),
            'clock_out' => Carbon::tomorrow()->setTime(18, 0, 0),
        ]);

        $nextDate = Carbon::tomorrow()->format('Y-m-d');
        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$nextDate}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::tomorrow()->format('Y年n月j日'));
    }
}
