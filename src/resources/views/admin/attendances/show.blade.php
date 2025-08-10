@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_detail.css') }}" />
@endsection

@section('content')
<div class="attendance-detail__container">
    <div class="attendance-detail__heading">
        <h2>勤怠詳細</h2>
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

    <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST"></form>
    @csrf
    @method('PUT')

    <div class="admin-attendance-detail__card">
        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">名前</span>
            <span class="admin-attendance-detail__data">{{ $attendance->user->name }}</span>
        </div>

        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">日付</span>
            <span class="admin-attendance-detail__data">{{ $attendance->date }}</span>
        </div>

        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">出勤・退勤</span>
            <div class="admin-attendance-detail__time-wrapper">
                <input type="time" name="check_in_time" value="{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }}">
                <span>~</span>
                <input type="time" name="check_out_time" value="{{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') }}">
            </div>
        </div>

        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">休憩</span>
            <div class="admin-attendance-detail__break-wrapper">
                <input type="number" name="break_in_time_1" value="{{ $attendance->break_in_time_1 ? \Carbon\Carbon::parse($attendance->break_in_time_1)->format('H:i') : '' }}">
                <span>~</span>
                <input type="time" name="break_out_time_1" value="{{ $attendance->break_out_time_1 ? \Carbon\Carbon::parse($attendance->break_out_time_1)->format('H:i') : '' }}">
            </div>
        </div>

        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">休憩2</span>
            <div class="admin-attendance-detail__break-wrapper">
                <input type="number" name="break_in_time_2" value="{{ $attendance->break_in_time_2 ? \Carbon\Carbon::parse($attendance->break_in_time_2)->format('H:i') : '' }}">
                <span>~</span>
                <input type="time" name="break_out_time_1" value="{{ $attendance->break_out_time_2 ? \Carbon\Carbon::parse($attendance->break_out_time_2)->format('H:i') : '' }}">
            </div>
        </div>

        <div class="admin-attendance-detail__item">
            <span class="admin-attendance-detail__label">備考</span>
            <div class="admin-attendance-detail__textarea-wrapper">
                <textarea class="admin-attendance-detail__textarea" name="note">
                    {{ $attendance->note }}
                </textarea>
            </div>
        </div>

    </div>
    <div class="admin-attendance-detail__action">
        <button type="submit" class="admin-attendance-detail__update-button">
            修正
        </button>
    </div>
</div>
@endsection
