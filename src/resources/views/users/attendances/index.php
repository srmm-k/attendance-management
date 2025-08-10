@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}" />
@endsection

@section('content')
<div class="attendance-list__container">
    <div class="attendance-list__heading">
        <h2>勤怠一覧</h2>
    </div>

    <div class="attendance-list__month-navigation">
        <a href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="attendance-list__nav-link">←前月</a>
        <span class="attendance-list__current-month">
            {{-- 例:2025/08 --}}
        </span>
        <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="attendance-list__nav-link">翌月→</a>
    </div>

    <table class="attendance-list__table">
        <tr class="attendance-list__row">
            <th class="attendance-list__label">日付</th>
            <th class="attendance-list__label">出勤</th>
            <th class="attendance-list__label">退勤</th>
            <th class="attendance-list__label">休憩</th>
            <th class="attendance-list__label">合計</th>
            <th class="attendance-list__label">詳細</th>
        </tr>
        @foreach($attendances as $attendance)
        <tr class="attendance-list__row">
            <td class="attendance-list__data">{{ $attendance->date }}</td>
            <td class="attendance-list__data">{{ $attendance->check_in_time }}</td>
            <td class="attendance-list__data">{{ $attendance->check_out_time }}</td>
            <td class="attendance-list__data">{{ floor($attendance->break_time /60) }}時間{{ $attendance->break_time % 60 }}分</td>
            <td class="attendance-list__data">{{ floor($attendance->total_time / 60) }}時間{{ $attendance->total_time % 60 }}分</td>
            <td class="attendance-list__data">
                <a href="{{ route('attendance.detail',['id' => $attendance->id]) }}" class="attendance-list__link">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
    <div class="attendance-list__pagination">
        {{ $attendances->links() }}
    </div>
</div>
@endsection