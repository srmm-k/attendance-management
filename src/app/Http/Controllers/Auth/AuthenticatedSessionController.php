<?php

namespace App\Http\Controllers\Auth;

use App\Providers\RouteServiceProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // ログインビューを返す
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        //認証情報の検証
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            //ユーザーが管理者かどうかをチェック
            if (Auth::user()->is_admin) {
                return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
            }
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        //認証失敗
        return back()->withErrors([
            'email' => '認証情報が登録されていません。',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {

        Auth::guard('web')->logout(); // 現在の認証ガード（web）からユーザーをログアウトさせる


        $request->session()->invalidate(); // 現在のセッションを無効化する（セッションデータをクリア）
        $request->session()->regenerateToken(); // 新しいCSRFトークンを再生成する

        return redirect('/');
    }
}