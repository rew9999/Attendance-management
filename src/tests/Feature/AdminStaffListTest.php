<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_view_all_staff_names_and_emails()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create([
            'name' => 'テストユーザー1',
            'email' => 'test1@example.com',
            'role' => 'employee',
        ]);
        $user2 = User::factory()->create([
            'name' => 'テストユーザー2',
            'email' => 'test2@example.com',
            'role' => 'employee',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');
        $response->assertStatus(200);
        $response->assertSee('テストユーザー1');
        $response->assertSee('test1@example.com');
        $response->assertSee('テストユーザー2');
        $response->assertSee('test2@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_staff_attendance_is_displayed_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $clockIn = Carbon::now()->setTime(9, 0, 0);
        $clockOut = Carbon::now()->setTime(18, 0, 0);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);
        $response->assertSee($clockIn->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_displays_previous_month_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->subMonth(),
            'clock_in' => Carbon::now()->subMonth()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->subMonth()->setTime(18, 0, 0),
        ]);

        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month={$previousMonth}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->subMonth()->format('Y/m'));
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_button_displays_next_month_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->addMonth(),
            'clock_in' => Carbon::now()->addMonth()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->addMonth()->setTime(18, 0, 0),
        ]);

        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month={$nextMonth}");
        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->addMonth()->format('Y/m'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_navigates_to_attendance_detail_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/date/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
