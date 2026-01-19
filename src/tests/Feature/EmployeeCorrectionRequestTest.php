<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * @group validation
     */
    public function test_clock_in_after_clock_out_shows_error()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '19:00',
            'requested_clock_out' => '18:00',
            'reason' => 'テスト理由',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * @group validation
     */
    public function test_break_start_after_clock_out_shows_error()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->setTime(12, 0, 0),
            'break_end' => Carbon::now()->setTime(13, 0, 0),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'breaks' => [
                [
                    'break_id' => $break->id,
                    'requested_break_start' => '19:00',
                    'requested_break_end' => '20:00',
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * @group validation
     */
    public function test_break_end_after_clock_out_shows_error()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->setTime(12, 0, 0),
            'break_end' => Carbon::now()->setTime(13, 0, 0),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'breaks' => [
                [
                    'break_id' => $break->id,
                    'requested_break_start' => '12:00',
                    'requested_break_end' => '19:00',
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_reason_is_required()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertEquals('備考を記入してください', session('errors')->get('reason')[0]);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_correction_request_is_created()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '09:30',
            'requested_clock_out' => '18:30',
            'reason' => '電車遅延のため',
        ]);

        $response->assertRedirect('/attendance/list');

        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => '電車遅延のため',
            'status' => 'pending',
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_pending_correction_requests_are_displayed()
    {
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

        $response = $this->actingAs($user)->get('/correction/requests');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_approved_correction_requests_are_displayed()
    {
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
        ]);

        $response = $this->actingAs($user)->get('/correction/requests?status=approved');
        $response->assertStatus(200);
        $response->assertSee('承認済み');
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_correction_request_detail_button_navigates_to_detail_page()
    {
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
            'reason' => 'テスト理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get("/correction/requests/{$correctionRequest->id}");
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
    }
}
