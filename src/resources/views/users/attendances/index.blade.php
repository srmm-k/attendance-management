@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}" />
@endsection

@section('content')
<div class="attendance-list__container">
    <div class="attendance-list__header-group">
        <h2 class="attendance-list__heading">勤怠一覧</h2>
    </div>

    <div class="attendance-list__card">
        <div class="attendance-list__month-navigation">
            <a href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="attendance-list__nav-link">←前月</a>
                <span class="attendance-list__current-month">
                    <img src="{{ asset('img/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                    {{ $year }}/{{ sprintf('%02d', $month) }}
                </span>
            <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="attendance-list__nav-link">翌月→</a>
        </div>

        <table class="attendance-list__table">
            <thead>
                <tr class="attendance-list__header-row">
                    <th class="attendance-list__label">日付</th>
                    <th class="attendance-list__label">出勤</th>
                    <th class="attendance-list__label">退勤</th>
                    <th class="attendance-list__label">休憩</th>
                    <th class="attendance-list__label">合計</th>
                    <th class="attendance-list__label">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daysInMonth as $day)
                    @php
                        $dateString = $day->toDateString();
                        $attendance = $organizedAttendances[$dateString] ?? null;

                        // 休憩時間と合計時間の初期化（$attendanceが存在しなくても初期値が必要）
                        $displayCheckIn = null;
                        $displayCheckOut = null;
                        $totalBreakTimeMinutes = 0;
                        $totalWorkTimeMinutes = 0;
                        $reasonData = null; // $attendanceがない場合はnullで初期化

                        if ($attendance) {

                            $displayCheckIn = $attendance->check_in_time;
                            $displayCheckOut = $attendance->check_out_time;

                            if ($attendance->application) {
                                $app = $attendance->application;
                                $reasonData = json_decode($app->reason, true) ?? [];

                                // 承認済み (status=2) または承認待ち (status=1) の申請時刻を優先
                                if ($app->status == 2 || $app->status == 1) {
                                    $displayCheckIn = $reasonData['check_in_time'] ?? $displayCheckIn;
                                    $displayCheckOut = $reasonData['check_out_time'] ?? $displayCheckOut;
                                }
                            }

                            // 休憩時間の計算に使用する休憩リストを決定
                            $breaksToCalculate = collect();

                            if ($reasonData && ($attendance->application->status == 1 || $attendance->application->status == 2)) {
                                // 申請JSONの休憩リストを使用
                                $breaksToCalculate = collect($reasonData['breaks'] ?? []);
                            } elseif ($attendance->breaks) {
                                // 元の勤怠レコードの休憩リストを使用
                                $breaksToCalculate = $attendance->breaks;
                            }

                            // 休憩時間の合計を計算
                            foreach ($breaksToCalculate as $break) {
                                // $breakがモデルオブジェクトか配列かを判断
                                $breakInTime = $break['break_in_time'] ?? $break->break_in_time ?? null;
                                $breakOutTime = $break['break_out_time'] ?? $break->break_out_time ?? null;

                                try {
                                    if ($breakInTime && $breakOutTime) {
                                        $breakIn = \Carbon\Carbon::parse($breakInTime);
                                        $breakOut = \Carbon\Carbon::parse($breakOutTime);
                                        $totalBreakTimeMinutes += $breakIn->diffInMinutes($breakOut);
                                    }
                                } catch (\Exception $e) {}
                            }

                            // 合計勤務時間（実労働時間）を計算
                            try {
                                if ($displayCheckIn && $displayCheckOut) {
                                    $checkIn = \Carbon\Carbon::parse($displayCheckIn);
                                    $checkOut = \Carbon\Carbon::parse($displayCheckOut);

                                    $totalTime = $checkIn->diffInMinutes($checkOut);
                                    $totalWorkTimeMinutes = $totalTime - $totalBreakTimeMinutes;
                                }
                            } catch (\Exception $e) {
                                $totalWorkTimeMinutes = 0;
                            }

                        }

                        // 分単位の時間を H:i 形式に変換するヘルパー関数
                        $formatMinutes = function ($minutes) {
                            if ($minutes <= 0) return '0:00';
                            $hours = floor($minutes / 60);
                            $mins = $minutes % 60;
                            return sprintf('%d:%02d', $hours, $mins);
                        };
                    @endphp

                <tr class="attendance-list__data-row">
                    <!-- 日付を表示 -->
                    <td class="attendance-list__data">
                        {{ $day->isoFormat('MM/DD(ddd)') }}
                    </td>
                    <!-- 出勤時間を表示 -->
                    <td class="attendance-list__data">
                        {{ $displayCheckIn ? \Carbon\Carbon::parse($displayCheckIn)->format('H:i') : '' }}
                    </td>

                    <!-- 退勤時間を表示 -->
                    <td class="attendance-list__data">
                        {{ $displayCheckOut ? \Carbon\Carbon::parse($displayCheckOut)->format('H:i') : '' }}
                    </td>

                    <!-- 休憩時間を表示 -->
                    <td class="attendance-list__data">
                        {{ $totalBreakTimeMinutes > 0 ? $formatMinutes($totalBreakTimeMinutes) : '' }}
                    </td>

                    <!-- 合計時間を表示 -->
                    <td class="attendance-list__data">
                        {{ $totalWorkTimeMinutes > 0 ? $formatMinutes($totalWorkTimeMinutes) : '' }}
                    </td>
                    <td class="attendance-list__data">
                    @php
                        // リンクに渡すIDを定義
                        $linkId = $day->toDateString();
                    @endphp

                    <a href="{{ route('attendance.show', ['id' => $linkId]) }}" class="attendance-list__link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection