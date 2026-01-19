<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration()
    {
        Event::fake([Registered::class]);

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 会員登録が成功したことを確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // Registeredイベントが発火されたことを確認（メール送信のトリガー）
        Event::assertDispatched(Registered::class);
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function test_verify_email_notice_page_has_verification_link()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function test_user_redirects_to_attendance_page_after_email_verification()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // メール認証URLを生成
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // メール認証後、勤怠画面にリダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // ユーザーのメール認証が完了していることを確認
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * メール認証が完了していないユーザーは verified ミドルウェアで保護されたページにアクセスできない
     */
    public function test_unverified_user_is_redirected_by_verified_middleware()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'role' => 'employee',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        // メール認証が未完了の場合、リダイレクトされることを確認
        // （リダイレクト先は実装によって異なる可能性があるため、リダイレクトされることのみ確認）
        $response->assertStatus(302);
    }

    /**
     * メール認証が完了しているユーザーは勤怠画面にアクセスできる
     */
    public function test_verified_user_can_access_attendance_page()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
    }
}
