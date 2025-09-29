@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance_list.css') }}" />
@endsection

@section('content')
<div class="staff-attendance-list__container">
    <div class="staff-attendance-list__header">
        <h2 class="staff-attendance-list__user-name">
            {{ $user->name }}さんの勤怠
        </h2>
    </div>

    <div class="staff-attendance-list__card">
        <div class="staff-attendance-list__month-navigation">
            <a href="{{ route('admin.users.attendances', ['id' => $user->id, 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="staff-attendance-list__nav-link">
                ←前月
            </a>
                <span class="staff-attendance-list__current-date">
                    <img src="{{ asset('img/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                    {{ $year}}/{{ $month }}
                </span>
            <a href="{{ route('admin.users.attendances', ['id' => $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="staff-attendance-list__nav-link">
                翌月→
            </a>
        </div>
    </div>

    <table class="staff-attendance-list__table">
        <tr class="staff-attendance-list__row">
            <th class="staff-attendance-list__label">日付</th>
            <th class="staff-attendance-list__label">出勤</th>
            <th class="staff-attendance-list__label">退勤</th>
            <th class="staff-attendance-list__label">休憩</th>
            <th class="staff-attendance-list__label">合計</th>
            <th class="staff-attendance-list__label">詳細</th>
        </tr>

        @foreach($daysInMonth as $date)
        @php
            // 日付文字列 (YYYY-MM-DD) を取得
            $dateString = $date->toDateString();
            // 整理された勤怠データから、その日のデータを取得
            $attendance = $organizedAttendances->get($dateString);

            // 申請を考慮した表示値の初期化
            $displayCheckIn = null;
            $displayCheckOut = null;
            $totalBreakTimeMinutes = 0;
            $totalWorkTimeMinutes = 0;
            $reasonData = null;

            // 勤怠レコードが存在する場合にのみ処理
            if ($attendance) {
                // 元の時刻を初期値とする
                $displayCheckIn = $attendance->check_in_time;
                $displayCheckOut = $attendance->check_out_time;

                // 申請データが存在する場合
                if ($attendance->application) {
                    $app = $attendance->application;
                    $reasonData = json_decode($app->reason, true) ?? [];

                    // 承認済み (status=2) または承認待ち (status=1) の申請時刻を優先
                    if ($app->status == 2 || $app->status == 1) {
                        $displayCheckIn = $reasonData['check_in_time'] ?? $displayCheckIn;
                        $displayCheckOut = $reasonData['check_out_time'] ?? $displayCheckOut;
                    }
                }

                // 休憩時間の計算に使用するリスト
                $breaksToCalculate = collect();

                // 承認済み or 承認待ちの申請があり、JSONに休憩データがある場合はそちらを優先
                if ($reasonData && ($attendance->application?->status == 1 || $attendance->application?->status == 2)) {
                    $breaksToCalculate = collect($reasonData['breaks'] ?? []);
                } elseif ($attendance->breaks) {
                    // 申請がない場合は元の勤怠レコードの休憩リストを使用
                    $breaksToCalculate = $attendance->breaks;
                }

                // 休憩時間の合計を計算
                foreach ($breaksToCalculate as $break) {
                    // $breakがモデルオブジェクトか配列かを判断
                    $breakInTime = $break['break_in_time'] ?? $break->break_in_time ?? null;
                    $breakOutTime = $break['break_out_time'] ?? $break->break_out_time ?? null;

                    if ($breakInTime && $breakOutTime) {
                        $breakIn = \Carbon\Carbon::parse($breakInTime);
                        $breakOut = \Carbon\Carbon::parse($breakOutTime);
                        $totalBreakTimeMinutes += $breakIn->diffInMinutes($breakOut);
                    }
                }

                // 合計勤務時間を計算
                if ($displayCheckIn && $displayCheckOut) {
                    $checkIn = \Carbon\Carbon::parse($displayCheckIn);
                    $checkOut = \Carbon\Carbon::parse($displayCheckOut);

                    $totalTime = $checkIn->diffInMinutes($checkOut);
                    $totalWorkTimeMinutes = max(0, $totalTime - $totalBreakTimeMinutes);
                }

            }
            // --- ここまで申請ロジック ---

            // 時間を H:i 形式で表示するためのヘルパー関数を再定義 (申請ロジックで計算された値を使う)
            $formatTime = function($time) {
                if (!$time) {
                    return '';
                }
                return \Carbon\Carbon::parse($time)->format('H:i');
            };

            // 分を 'H:i' 形式にフォーマットするヘルパー関数
            $formatMinutes = function($minutes) {
                // 0分以下の場合は空文字
                if (is_null($minutes) || $minutes <= 0) return '';
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return sprintf('%d:%02d', $hours, $mins);
            };
        @endphp

        <tr class="staff-attendance-list__data-row">
            {{-- 日付 --}}
            <td class="staff-attendance-list__data">
                {{ $date->isoFormat('MM/DD(ddd)') }}
            </td>

            {{-- 出勤 --}}
            <td class="staff-attendance-list__data">
                {{ $displayCheckIn ? $formatTime($displayCheckIn) : '' }}
            </td>

            {{-- 退勤 --}}
            <td class="staff-attendance-list__data">
                {{ $displayCheckOut ? $formatTime($displayCheckOut) : '' }}
            </td>

            {{-- 休憩 --}}
            <td class="staff-attendance-list__data">
                {{ $formatMinutes($totalBreakTimeMinutes) }}
            </td>

            {{-- 合計 --}}
            <td class="staff-attendance-list__data">
                {{ $formatMinutes($totalWorkTimeMinutes) }}
            </td>

            {{-- 詳細 --}}
            <td class="staff-attendance-list__data">
                @php
                    // リンクに渡すIDを定義
                    if ($attendance) {
                    // 勤怠データがある場合: 実際の勤怠IDを渡す
                    $linkId = $attendance->id;
                } else {
                    // 勤怠データがない場合: ユーザーIDと日付を結合した文字列をIDとして渡す
                    $linkId = $user->id . '-' . $dateString;
                    }
                @endphp

                <a href="{{ route('attendance.show', ['id' => $linkId]) }}" class="staff-attendance-list__link">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="staff-attendance-list__footer">
        <a href="{{ route('admin.users.attendances.exportCsv', ['id' => $user->id, 'year' => $year, 'month' => $month]) }}" class="staff-attendance-list__csv-button">CSV出力</a>
    </div>
</div>
@endsection
