<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomLoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->routeIs('admin.login')) {
            return view('admin.login');
        }
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {

        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        // URLに"admin/login"が含まれているかで判断
        if (str_contains($request->url(), 'admin/login')) {
            if (!$user || !$user->is_admin || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'ログイン情報が登録されていません',
                ]);
            }

            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(RouteServiceProvider::ADMIN_HOME);

        } else {
            if (!$user || $user->is_admin || !Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'ログイン情報が登録されていません',
                ]);
            }

            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(RouteServiceProvider::HOME);
        }
    }
}
