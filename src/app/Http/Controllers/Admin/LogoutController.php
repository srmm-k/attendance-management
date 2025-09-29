<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * 認証済みセッションを破棄します。
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout(); // 'web'ガードを使用してログアウト

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/admin/login'); // 管理者ログインページへリダイレクト
    }
}
