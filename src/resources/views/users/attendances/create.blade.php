@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user_attendance_create.css') }}" />
@endsection

@section('content')
<div class="attendance-create__container">
    <div class="attendance-create__heading">
        <h2>勤怠登録</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="attendance-create__card">
        <p class="attendance-create__date">{{ \Carbon\Carbon::now()->format('Y年m月d日') }}</p>

        <form method="POST" action="{{ route('attendance.store') }}" class="attendance-create__form">
            @csrf

            <!-- {{-- 出勤ボタン --}} -->
            @if(empty($todayAttendance) || empty($todayAttendance->check_in_time))
                <button type="submit" name="checkin" class="attendance-create__button attendance-create__button--checkin">出勤</button>
            @endif

            <!-- {{-- 休憩入/休憩戻/退勤ボタン --}} -->
            @if(!empty($todayAttendance) && !empty($todayAttendance->check_in_time) && empty($todayAttendance->check_out_time))
            <!-- {{-- 休憩中かどうかを判定 --}} -->
                @php
                    $isBreaking = false;
                    if (!empty($todayAttendance->break_in_time_1) && empty($todayAttendance->break_out_time_1)) {
                        $isBreaking = true;
                    } elseif (!empty($todayAttendance->break_in_time_2) && empty($todayAttendance->break_out_time_2)) {
                        $isBreaking = true;
                    }
                @endphp

                @if($isBreaking)
                    <!-- {{-- 休憩中の場合：休憩戻ボタン --}} -->
                    <button type="submit" name="break_out" class="attendance-create__button attendance-create__button--break-out">休憩戻</button>
                @else
                    <!-- {{-- 休憩中でない場合：休憩入ボタン（２回目の休憩まで可能 --）}} -->
                    @if(empty($todayAttendance->break_in_time_2) || empty($todayAttendance->break_out_time_2))
                        <button type="submit" name="break_in" class="attendance-create__button attendance-create__button--break-in">休憩入</button>
                    @endif
                    <!-- {{-- 退勤ボタン --}} -->
                    <button type="submit" name="checkout" class="attendance-create__button attendance-create__button--checkout">退勤</button>
                @endif
            @endif

            <!-- {{-- 退勤済みの場合 --}} -->
            @if(!empty($todayAttendance) && !empty($todayAttendance->check_out_time))
                <p class="attendance-create__message">お疲れ様でした！</p>
            @endif
        </form>
    </div>
</div>
@endsection