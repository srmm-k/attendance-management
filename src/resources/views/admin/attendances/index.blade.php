@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}" />
@endsection

@section('content')
<div class="admin-attendance-list__container">
    <div class="admin-attendance-list__heading">
        <h2>勤怠一覧</h2>
    </div>

    <div class="admin-attendance-list__month-navigation">
        <a href="{{ route('admin.attendances', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" class="admin-attendance-list__nav-link">←前月</a>
        <span class="admin-attendance-list__current-month">
            {{ $year }}年{{ $month }}
        </span>
        <a href="{{ route('admin.attendances', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="admin-attendance-list__nav-link">翌月→</a>
    </div>

    <table class="admin-attendance-list__table">
        <tr class="admin-attendance-list__row">
            <th class="admin-attendance-list__label">名前</th>
            <th class="admin-attendance-list__label">日付</th>
            <th class="admin-attendance-list__label">出勤時間</th>
            <th class="admin-attendance-list__label">退勤時間</th>
            <th class="admin-attendance-list__label">休憩時間</th>
            <th class="admin-attendance-list__label">合計時間</th>
            <th class="admin-attendance-list__label">詳細</th>
        </tr>
        @foreach($attendances as $attendance)
        <tr class="admin-attendance-list__row">
            <td class="admin-attendance-list__data">{{ $attendance->user->name }}</td>
            <td class="admin-attendance-list__data">{{ $attendance->date }}</td>
            <td class="admin-attendance-list__data">{{ $attendance->check_in_time }}</td>
            <td class="admin-attendance-list__data">{{ $attendance->check_out_time }}</td>
            <td class="admin-attendance-list__data">{{ floor($attendance->break_time / 60) }}時間{{ $attendance->break_time % 60 }}分</td>
            <td class="admin-attendance-list__data">{{ floor($attendance->total_time / 60)}}時間{{ $attendance->total_time % 60 }}分</td>
            <td class="admin-attendance-list__data">
                <a href="{{ route('admin.attendances.detail', ['id' => $attendance->id]) }}" class="admin-attendance-list__link">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="admin-attendance-list__pagination">
        {{ $attendances->links() }}
    </div>
</div>
@endsection