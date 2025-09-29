@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}" />
@endsection

@section('content')
<div class="admin-attendance-list__container">
    <div class="admin-attendance-list__header-group">
        <h2 class="admin-attendance-list__heading">
            {{ $carbonDate->isoFormat('Y年M月D日') }}の勤怠
        </h2>
    </div>

    <div class="admin-attendance-list__card">
        <div class="admin-attendance-list__day-navigation">
            <a href="{{ route('admin.attendances.list', ['date' => $prevDay->toDateString()]) }}" class="admin-attendance-list__nav-link">←前日</a>
            <span class="admin-attendance-list__current-day">
                <img src="{{ asset('img/calendar_icon.svg') }}" alt="カレンダーアイコン" class="calendar-icon">
                {{ $carbonDate->isoFormat('Y/MM/DD') }}
            </span>
            <a href="{{ route('admin.attendances.list', ['date' => $nextDay->toDateString()]) }}" class="admin-attendance-list__nav-link">翌日→</a>
        </div>

        <table class="admin-attendance-list__table">
            <tr class="admin-attendance-list__row">
                <th class="admin-attendance-list__label">名前</th>
                <th class="admin-attendance-list__label">出勤時間</th>
                <th class="admin-attendance-list__label">退勤時間</th>
                <th class="admin-attendance-list__label">休憩時間</th>
                <th class="admin-attendance-list__label">合計時間</th>
                <th class="admin-attendance-list__label">詳細</th>
            </tr>
            @foreach($attendances as $attendance)
            <tr class="admin-attendance-list__data-row">
                <td class="admin-attendance-list__data">
                    {{ $attendance->user->name ?? '-' }}
                </td>
                <td class="admin-attendance-list__data">
                    {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') ?? '-' }}
                </td>
                <td class="admin-attendance-list__data">
                    {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') ?? '-' }}
                </td>
                <td class="admin-attendance-list__data">
                    {{ floor($attendance->break_time /60) }}:{{ sprintf('%02d', $attendance->break_time % 60) }}
                </td>
                <td class="admin-attendance-list__data">
                    {{ floor($attendance->total_time / 60) }}:{{ sprintf('%02d', $attendance->total_time % 60) }}
                </td>
                <td class="admin-attendance-list__data">
                    <a href="{{ route('attendance.show', ['id' => $attendance->id]) }}" class="admin-attendance-list__link">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>

        <div class="admin-attendance-list__pagination">
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection