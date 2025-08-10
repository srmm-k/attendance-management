@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_applications_index.css') }}" />
@endsection

@section('content')
<div class="admin-requests-index__container">
    <div class="admin-requests-index__heading">
        <h2>申請一覧</h2>
    </div>

    @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($applications->isEmpty())
        <p class="no-requests-message">まだ申請はありません。</p>
    @else
        <table class="admin-requests-index__table">
            <thead>
                <tr>
                    <th class="admin-requests-index__label">申請者名</th>
                    <th class="admin-requests-index__label">対象日付</th>
                    <th class="admin-requests-index__label">申請理由</th>
                    <th class="admin-requests-index__label">状態</th>
                    <th class="admin-requests-index__label">申請日時</th>
                    <th class="admin-requests-index__label">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $application)
                <tr>
                    <td class="admin-requests-index__data">{{ $application->user->name }}</td>
                    <td class="admin-requests-index__data">{{ $application->target_date }}</td>
                    <td class="admin-requests-index__data">{{ Str::limit($application->reason, 50) }}</td>
                    <td class="admin-requests-index__data">
                        @if($application->status == 1) 承認待ち
                        @elseif($application->status == 2) 承認済み
                        @elseif($application->status == 3) 却下
                        @endif
                    </td>
                    <td class="admin-requests-index__data">{{ $application->created_at->format('Y-m-d H:i') }}</td>
                    <td class="admin-requests-index__data">
                        <a href="{{ route('admin.requests.detail', ['id' => $application->id]) }}" class="admin-requests-index__link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="admin-requests-index__pagination">
            {{ $applications->links() }}
        </div>
    @endif
</div>
@endsection