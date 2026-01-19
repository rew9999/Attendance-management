<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されている
     */
    public function test_pending_correction_requests_are_displayed()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => Carbon::now()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::now()->setTime(18, 30, 0),
            'reason' => 'テスト理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
    }

    /**
     * 承認済みの修正申請が全て表示されている
     */
    public function test_approved_correction_requests_are_displayed()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => Carbon::now()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::now()->setTime(18, 30, 0),
            'reason' => 'テスト理由',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list?status=approved');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_correction_request_detail_is_displayed_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
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

        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => Carbon::now()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::now()->setTime(18, 30, 0),
            'reason' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get("/admin/stamp_correction_request/approve/attendance/{$correctionRequest->id}");
        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('電車遅延のため');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_correction_request_approval_works_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => Carbon::now()->setTime(9, 30, 0),
            'requested_clock_out' => Carbon::now()->setTime(18, 30, 0),
            'reason' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/stamp_correction_request/approve/attendance/{$correctionRequest->id}");

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        // 勤怠情報が更新されているか確認
        $attendance->refresh();
        $this->assertEquals('09:30:00', $attendance->clock_in->format('H:i:s'));
        $this->assertEquals('18:30:00', $attendance->clock_out->format('H:i:s'));
    }
}
