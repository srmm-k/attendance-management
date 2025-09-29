@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_applications_show.css') }}" />
@endsection

@section('content')
<div class="admin-requests-show__container">
    <div class="admin-requests-show__header-group">
        <h2 class="admin-requests-show__heading">勤怠詳細</h2>
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

    @php
        // 修正申請のJSONデータをデコード
        $reasonData = $application->reason ? json_decode($application->reason, true) : [];
        $breaks = $reasonData['breaks'] ?? [];
    @endphp

    <div class="admin-requests-show__card">
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">名前</span>
            <span class="admin-requests-show__data">{{ $application->user->name }}</span>
        </div>
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">日付</span>
                <div class="admin-requests-show__data">
                    <span>{{ \Carbon\Carbon::parse($application->target_date)->isoFormat('YYYY年') }}</span>
                    <span>{{ \Carbon\Carbon::parse($application->target_date)->isoFormat('M月D日') }}</span>
            </div>
        </div>
        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">出勤・退勤</span>
            <div class="admin-requests-show__time-wrapper">
                <span>
                    {{ $reasonData['check_in_time'] ?? '-' }}
                </span>
                <span>~</span>
                <span>
                    {{ $reasonData['check_out_time'] ?? '-' }}
                </span>
            </div>
        </div>

        @forelse($breaks as $index => $break)
            @php
                $breakIn = $break['break_in_time'] ?? null;
                $breakOut = $break['break_out_time'] ?? null;
            @endphp

            @if (!$breakIn && !$breakOut)
                @continue
            @endif
            
            <div class="admin-requests-show__item">
                <span class="admin-requests-show__label">
                    @if ($index === 0)
                        休憩
                    @else
                        休憩{{ $index + 1 }}
                    @endif
                </span>
                <div class="admin-requests-show__break-wrapper">
                    <span>
                        {{ $break['break_in_time'] ?? '-'}}
                    </span>
                    <span>~</span>
                    <span>
                        {{ $break['break_out_time'] ?? '-'}}
                    </span>
                </div>
            </div>
        @empty
            <div class="admin-requests-show__item">
                <span class="admin-requests-show__label">休憩</span>
                <div class="admin-requests-show__break-wrapper">
                    <span>休憩記録なし</span>
                </div>
            </div>
        @endforelse

        <div class="admin-requests-show__item">
            <span class="admin-requests-show__label">備考</span>
            <div class="admin-requests-show__textarea-wrapper">
                <span class="admin-requests-show__reason-textarea">{{ $reasonData['note'] ?? '備考なし' }}</span>
            </div>
        </div>
    </div>

    <div class="admin-requests-show__action-form-container">
        @if($application->status == 1)
            <form action="{{ route('admin.requests.update', $application->id) }}" method="POST" class="admin-requests-show__action-form">
                @csrf
                @method('PUT')
                <button type="submit" name="status" value="2" class="admin-requests-show__button admin-requests-show__button--approve">承認</button>
            </form>
        @else
            <span class="admin-request-show__approved-text">承認済み</span>
        @endif
    </div>
</div>
@endsection