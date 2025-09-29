<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                //認証済みユーザーが管理者であるかチェック
                if (Auth::user()->is_admin) {
                    //管理者の場合は管理者ホームへリダイレクト
                    return redirect(RouteServiceProvider::ADMIN_HOME);
                }
                //一般ユーザーの場合は通常のホームへリダイレクト
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
