<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\LoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ログイン後のリダイレクト処理
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse
            {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    if ($user->role === 'admin') {
                        return redirect('/admin/attendance/list');
                    }

                    return redirect('/attendance');
                }
            };
        });

        // 会員登録後のリダイレクト処理
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse
            {
                public function toResponse($request)
                {
                    return redirect('/attendance');
                }
            };
        });

        // ログアウト後のリダイレクト処理
        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse
            {
                public function toResponse($request)
                {
                    // ログアウト前のURLからログイン先を判定
                    $previousUrl = url()->previous();

                    // 管理者画面からのログアウトかチェック
                    if (str_contains($previousUrl, '/admin/')) {
                        return redirect('/admin/login');
                    }

                    return redirect('/login');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ログイン画面（一般ユーザー）
        Fortify::loginView(function () {
            // 管理者ログインの場合
            if (request()->is('admin/login')) {
                return view('auth.admin-login');
            }

            // 一般ユーザーログイン
            return view('auth.login');
        });

        // 会員登録画面
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // メール認証画面
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ログインバリデーション
        Fortify::authenticateUsing(function (Request $request) {
            $loginRequest = LoginRequest::createFrom($request);
            $validated = $loginRequest->validate($loginRequest->rules());

            $user = \App\Models\User::where('email', $validated['email'])->first();

            if ($user && \Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
