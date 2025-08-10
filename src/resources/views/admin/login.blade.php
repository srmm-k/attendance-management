@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}" />
@endsection

@section('content')

<div class="login_content">
    <div class="login__heading">
        <h2>管理者ログイン</h2>
    </div>

    <form class="login-form" action="{{ route('login') }}" method="POST">
        @csrf

        <div class="form__group">
                <label for="email" class="form__label">
                    メールアドレス
                </label>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus />
                @error('email')
                <p class="form__error-message">{{ $message }}</p>
                @enderror
        </div>

        <div class="form__group">
                <label for="password" class="form__label">
                    パスワード
                </label>
                <input type="password" name="password" id="password" autocomplete="current-password" />
                @error('password')
                <p class="form__error-message">{{ $message }}</p>
                @enderror
        </div>
        <button class="login-button" type="submit">管理者ログインする</button>
    </form>
</div>
@endsection

