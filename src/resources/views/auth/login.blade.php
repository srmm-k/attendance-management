@extends('layouts/default')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}" />
@endsection

@section('content')

<div class="login-container">
    <div class="login__heading">
        <h2>ログイン</h2>
    </div>

    <form class="login-form" action="{{ route('login') }}" method="POST" novalidate>
        @csrf

        <div class="form__group">
            <label for="email" class="form__label">メールアドレス</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" autofocus />
            @error('email')
            <p class="form__error-message">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label for="password" class="form__label">パスワード</label>
            <input type="password" name="password" id="password" autocomplete="current-password" />
            @error('password')
            <p class="form__error-message">{{ $message }}</p>
            @enderror
        </div>

        <button class="login-button" type="submit">ログイン</button>
    </form>
    
    <div class="register-link-area">
        <a href="{{ route('register') }}" class="register-link">会員登録はこちら</a>
    </div>
</div>
@endsection
