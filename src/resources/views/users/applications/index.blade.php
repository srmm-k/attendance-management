@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/applications.css') }}" />
@endsection

@section('content')
<div class="container">
            <h2 class="application-list__heading">申請一覧</h2>

            <ul>
                <li class="nav-item">
                    <a class="nav-link {{request()->query('status') === 'pending' || !request()->query('status') ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'pending']) }}">承認待ち</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->query('status') === 'approved' ? 'active' : '' }}" href="{{ route('applications.index', ['status' => 'approved']) }}">承認済み</a>
                </li>
            </ul>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                    <tr>
                        <td>
                            @if ($application->status == 1)
                                承認待ち
                            @elseif ($application->status == 2)
                                承認済み
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $application->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($application->target_date)->format('Y/m/d') }}</td>
                        <td>{{ $application->reason ? json_decode($application->reason)->note : '備考なし'}}</td>
                        <td>{{ \Carbon\Carbon::parse($application->created_at)->format('Y/m/d') }}</td>
                        <td>
                            @if ($application->attendance)
                                <a href="{{ route('attendance.show', ['id' => $application->target_date->format('Y-m-d')]) }}" class="btn btn-sm btn-info">詳細</a>
                            @else
                                <span>勤怠情報なし</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">該当する申請はありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
</div>
@endsection