@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}" />
@endsection

@section('content')
<div class="member-registration__content">
    <div class="member-registration__heading">
        <h2>会員登録</h2>
    </div>
    <form class="form" action="{{ route('register') }}" method="POST" novalidate>
        @csrf
        <div class="form__group">
            <label for="name" class="form__label">名前</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="name" autofocus />
            @error('name')
                <p class="form__error-message">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label for="email" class="form__label">メールアドレス</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" />
            @error('email')
                <p class="form__error-message">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label for="password" class="form__label">パスワード</label>
            <input type="password" name="password" id="password" autocomplete="new-password" />
            <p class="form__note">※英字と数字を含む8文字以上で入力してください</p>
            @error('password')
                <p class="form__error-message">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label for="password_confirmation" class="form__label">パスワード確認</label>
            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" />
        </div>
        <button class="registration-button" type="submit">登録する</button>
        <a class="login" href="{{ route('login') }}">ログインはこちら</a>
    </form>
</div>
@endsection