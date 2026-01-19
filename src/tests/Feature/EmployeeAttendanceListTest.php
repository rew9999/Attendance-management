<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_all_own_attendance_records_are_displayed()
    {
        $user = User::factory()->create(['role' => 'employee']);
        $otherUser = User::factory()->create(['role' => 'employee']);

        // 自分の勤怠データ
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->subDay(),
            'clock_in' => Carbon::yesterday()->setTime(9, 0, 0),
            'clock_out' => Carbon::yesterday()->setTime(18, 0, 0),
        ]);

        // 他のユーザーの勤怠データ
        Attendance::create([
            'user_id' => $otherUser->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $this->assertEquals(2, Attendance::where('user_id', $user->id)->count());
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed_on_initial_load()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_displays_previous_month_data()
    {
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subMonth(),
            'clock_in' => Carbon::now()->subMonth()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->subMonth()->setTime(18, 0, 0),
        ]);

        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $response = $this->actingAs($user)->get("/attendance/list?month={$previousMonth}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->subMonth()->format('Y/m'));
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_button_displays_next_month_data()
    {
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->addMonth(),
            'clock_in' => Carbon::now()->addMonth()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->addMonth()->setTime(18, 0, 0),
        ]);

        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $response = $this->actingAs($user)->get("/attendance/list?month={$nextMonth}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->addMonth()->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get("/attendance/data/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
