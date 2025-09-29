@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user_attendance_create.css') }}" />
@endsection

@section('content')
<div class="attendance-create__container">

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="attendance-create__card">
        <div class="attendance-status-tag-container">
            @php
                // 現在の勤怠状況を判断するための変数群
                $isCheckIn = !empty($todayAttendance) && !empty($todayAttendance->check_in_time);
                $isCheckOut = $isCheckIn && !empty($todayAttendance->check_out_time);

                // 休憩中かどうかを判定
                $isBreaking = false;
                //出勤中でまだ退勤していない場合のみ休憩状態を判定
                if ($isCheckIn && !$isCheckOut) {
                    $latestBreak = $todayAttendance->breaks->last();
                    if ($latestBreak &&  empty($latestBreak->break_out_time)){
                        $isBreaking = true;
                    }
                }

                // ステータス表示のテキストとクラス
                $statusText = '勤務外';
                $statusClass = 'status-tag--outside';

                if ($isCheckOut) {
                    $statusText = '退勤済';
                    $statusClass = 'status-tag--checkout';
                } elseif ($isBreaking) {
                    $statusText = '休憩中';
                    $statusClass = 'status-tag--breaking';
                } elseif ($isCheckIn) {
                    $statusText = '出勤中';
                    $statusClass = 'status-tag--checkin';
                }
            @endphp
            <span class="attendance-status-tag {{ $statusClass }}">
                {{ $statusText }}
            </span>
        </div>

        <p class="attendance-create__date">
            {{ \Carbon\Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)') }}
        </p>
        <p class="attendance-create__current-time" id="currentTime"></p>

        <form method="POST" action="{{ route('attendance.store') }}" class="attendance-create__form">
            @csrf

            <div class="attendance-create__buttons">
                <!-- 出勤ボタン -->
                @if(!$isCheckIn)
                    <button type="submit" name="checkin" class="attendance-create__button attendance-create__button--checkin">出勤</button>
                @endif

                <!-- 退勤ボタン -->
                <!-- 出勤中で、退勤しておらず、かつ休憩中でない場合 -->
                @if($isCheckIn && !$isCheckOut && !$isBreaking)
                    <button type="submit" name="checkout" class="attendance-create__button attendance-create__button--checkout">退勤</button>
                @endif

                <!-- 休憩入ボタン -->
                <!-- 出勤中で、退勤しておらず、休憩中でなく、かつ２回休憩を終えていない場合 -->
                @if($isCheckIn && !$isCheckOut && !$isBreaking)
                    <button type="submit" name="break_in" class="attendance-create__button attendance-create__button--break-in">休憩入</button>
                @endif

                <!-- 休憩戻ボタン -->
                <!-- 休憩中の場合 -->
                @if($isBreaking)
                    <button type="submit" name="break_out" class="attendance-create__button attendance-create__button--break-out">休憩戻</button>
                @endif

            </div>

            <!-- {{-- 退勤済みの場合 --}} -->
            @if($isCheckOut)
                <p class="attendance-create__message">お疲れ様でした。</p>
            @endif
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
    //現在時刻をリアルタイムで表示
    function updateCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('currentTime').textContent = `${hours}:${minutes}`;
    }

    setInterval(updateCurrentTime, 1000);

    document.addEventListener('DOMContentLoaded', updateCurrentTime);
</script>
@endsection