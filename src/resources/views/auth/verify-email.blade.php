@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}" />
@endsection

@section('content')
<div class="verify__container">
    <p class="verify__message">
        登録していただいたメールアドレスに認証メールを送付しました。
    </p>
    <p class="verify__message">
        メール認証を完了してください。
    </p>

    <div class="verify__button-group">
        <a href="http://localhost:8025" target="_blank" class="verify__button">認証はこちらから</a>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify__resend-button">認証メールを再送する</button>
    </form>
</div>

@if (Auth::user()->hasVerifiedEmail())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        //ユーザーがメール認証を完了している場合、勤怠登録画面へリダイレクト
        window.location.href = "{{ route('attendance.create') }}"; //認証完了後のリダイレクト先
    });
</script>
@endif
@endsection