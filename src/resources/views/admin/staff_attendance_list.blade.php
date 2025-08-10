@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance_list.css') }}" />
@endsection

@section('content')
<div class="staff-attendance-list__container">
    <div class="staff-attendance-list__header">
        <h2 class="staff-attendance-list__user-name">{{ $user->name }}</h2>
        <div class="staff-attendance-list__date-nav">
            <a href="{{ route('admin.users.attendances, ['user' => $user->, 'year' => $prevMouth->year, 'month' => $prevMouth->month]) }}" class="staff-attendance-list__nav-link">
                &lt; 前月
            </a>
            <span class="staff-attendance-list__current-date">
                {{ $year}}年{{ $month }}月
            </span>
            <a href="{{ route('admin.users.attendances', ['user' => $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="staff-attendance-list__nav-link">
                翌月 &gt;
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
        @foreach($attendance as $attendance)
        <tr class="staff-attendance-list__row">
            <td class="staff-attendance-list__data">{{ \Carbon\Carbon::parse($attendance->date)->format('m/d(D)') }}</td>
            <td class="staff-attendance-list__data">{{ $attendance->check_in_time }}</td>
            <td class="staff-attendance-list__data">{{ $attendance->check_out_time }}</td>
            <td class="staff-attendance-list__data">{{ floor($attendance->break_time / 60) }}時間{{ $attendance->break_time % 60 }}分</td>
            <td class="staff-attendance-list__data">{{ floor($attendance->total_time / 60) }}時間{{ $attendance->total_time % 60 }}分</td>
            <td class="staff-attendance-list__data">
                <a href="{{ route('admin.attendances.detail', ['id => $attendance->id]) }}" class="staff-attendance-list__link">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>

    <div class="staff-attendance-list__pagination">
        {{ $attendances->links() }}
    </div>

    <div class="staff-attendance-list__footer">
        <a href="{{ route('admin.users.attendances.exportCsv', ['user' => $user->id, 'year' => $year, 'month' => $month]) }}" class="staff-attendance-list__csv-button">CSV出力</a>
    </div>
</div>
@endsection
