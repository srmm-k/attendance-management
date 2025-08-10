@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_list.css') }}" />
@endsection

@section('content')
<div class="staff-list__container">
    <div class="staff-list__heading">
        <h2>スタッフ一覧</h2>
    </div>

    <table class="staff-list__table">
        <tr class="staff-list__row">
            <th class="staff-list__label">名前</th>
            <th class="staff-list__label">メールアドレス</th>
            <th class="staff-list__label">月次勤怠</th>
        </tr>
        @foreach($users as $user)
        <tr class="staff-list__row">
            <td class="staff-list__data">{{ $user->name }}</td>
            <td class="staff-list__data">{{ $user->email }}</td>
            <td class="staff-list__data">
                <a href="{{ route('admin.users.attendances', ['user' => $user->id]) }}" class="staff-list__link">詳細</a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
