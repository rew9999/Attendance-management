<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_clock_in_cannot_be_after_clock_out()
    {
        $user = User::factory()->create(['role' => 'employee', 'email_verified_at' => now()]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now()->addHours(8),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '2025-01-01 18:00',
            'requested_clock_out' => '2025-01-01 09:00',
            'reason' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_start_cannot_be_after_clock_out()
    {
        $user = User::factory()->create(['role' => 'employee', 'email_verified_at' => now()]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now()->addHours(8),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '2025-01-01 09:00',
            'requested_clock_out' => '2025-01-01 18:00',
            'breaks' => [
                [
                    'requested_break_start' => '2025-01-01 19:00',
                    'requested_break_end' => '2025-01-01 20:00',
                ]
            ],
            'reason' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['breaks.0.requested_break_start' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_break_end_cannot_be_after_clock_out()
    {
        $user = User::factory()->create(['role' => 'employee', 'email_verified_at' => now()]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now()->addHours(8),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '2025-01-01 09:00',
            'requested_clock_out' => '2025-01-01 18:00',
            'breaks' => [
                [
                    'requested_break_start' => '2025-01-01 12:00',
                    'requested_break_end' => '2025-01-01 19:00',
                ]
            ],
            'reason' => '修正理由',
        ]);

        $response->assertSessionHasErrors(['breaks.0.requested_break_end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_reason_is_required()
    {
        $user = User::factory()->create(['role' => 'employee', 'email_verified_at' => now()]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now()->addHours(8),
        ]);

        $response = $this->actingAs($user)->post("/attendance/edit/request/{$attendance->id}", [
            'requested_clock_in' => '2025-01-01 09:00',
            'requested_clock_out' => '2025-01-01 18:00',
        ]);

        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }
}
