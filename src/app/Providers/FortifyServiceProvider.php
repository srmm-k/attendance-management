<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse;
use Illuminate\Cache\RateLimiting\Limit;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //ログインビューの表示をカスタマイズ
        Fortify::loginView(function (Request $request) {
            if ($request->routeIs('admin.login')) {
                return view('admin.login');
            }
            return view('auth.login');
        });
        

        //ログイン後のリダイレクト先をカスタマイズ
        Fortify::redirects('login', function (Request $request) {
            if ($request->user() && $request->user()->is_admin) {
                return RouteServiceProvider::ADMIN_HOME;
            }
            return RouteServiceProvider::HOME;
        });

        // ログアウト後のリダイレクト先をカスタマイズ
        Fortify::redirects('logout', function (Request $request) {
            if ($request->user() && $request->user()->is_admin) {
                // 管理者ユーザーを/admin/loginへリダイレクト
                return '/admin/login';
            }
            // 一般ユーザーを/loginへリダイレクト
            return '/login';
        });


        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        Fortify::redirects('register', 'verification.notice');

        $this->app->instance(VerifyEmailViewResponse::class, new class implements VerifyEmailViewResponse {
            public function toResponse($request)
            {
                return view('auth.verify-email', [
                    'status' => session('status'),
                ]);
            }
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
