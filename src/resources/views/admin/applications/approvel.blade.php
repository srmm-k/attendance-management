@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_applications_show.css') }}" />
@endsection

@section('content')
<div class="admin-request-show__container">
    <div class="admin-requests-show__heading">
        <h2>修正申請承認</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-requests-show__card">
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">申請者名：</span>
            <span class="admin-requests-show__data">{{ $application->user->name }}</span>
        </div>
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">対象日付：</span>
            <span class="admin-requests-show__data">{{ $application->target_date }}</span>
        </div>
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">申請理由：</span>
            <div class="admin-requests-show__reason-wrapper">
                <textarea class="admin-requests-show__reason-textarea" readonly>{{ $application->reason }}</textarea>
            </div>
        </div>
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">現状の状態：</span>
            <span class="admin-requests-show__data">
                @if($application->status == 1) 承認待ち
                @elseif($application->status == 2) 承認済み
                @elseif($application->status == 3) 却下
                @endif
            </span>
        </div>
    </div>

    <form action="{{ route('admin.requests.update', $application->id) }}" method="POST" class="admin-requests-show__action-form">
        @csrf
        @method('PUT')

        @if($application->status == 1) {{-- 承認待ちの場合のみボタンを表示 --}}
        <button type="submit" name="status" value="2" class="admin-requests-show__button admin-requests-show__button--approve">承認</button>
        <button type="submit" name="status" value="3" class="admin-requests-show__button admin-requests-show__button--reject">却下</button>
        @else
            <p class="admin-requests-show__status-message">この申請は既に処理済みです。</p>
        @endif
    </form>

    <div class="admin-requests-show__back-link">
        <a href="{{ route('admin.requests) }}">申請一覧へ戻る</a>
    </div>
</div>
@endsection