@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_detail.css') }}" />
@endsection

@section('content')
<div class="admin-attendance-detail__container">
    <div class="admin-attendance-detail__header-group">
        <h2 class="admin-attendance-detail__heading">勤怠詳細</h2>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('attendance.update', ['id' => $attendance->id ?? \Carbon\Carbon::parse($date)->format('Y-m-d')]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="admin-attendance-detail__card">
            <div class="admin-attendance-detail__item">
                <span class="admin-attendance-detail__label">名前</span>
                <span class="admin-attendance-detail__data">{{ $user->name }}</span>
            </div>

            <div class="admin-attendance-detail__item">
                <span class="admin-attendance-detail__label">日付</span>
                <div class="admin-attendance-detail__data admin-attendance-detail__data--date">
                    <span>{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年') }}</span>
                    <span>{{ \Carbon\Carbon::parse($date)->isoFormat('M月D日') }}</span>
                </div>
            </div>

            <div class="admin-attendance-detail__item">
                <span class="admin-attendance-detail__label">出勤・退勤</span>
                <div class="admin-attendance-detail__time-wrapper">
                    <input type="text" name="check_in_time" class="time-input" value="{{ $displayCheckIn }}" placeholder="00:00">
                    <span>~</span>
                    <input type="text" name="check_out_time" class="time-input" value="{{ $displayCheckOut }}" placeholder="00:00">
                </div>
            </div>

            @php
                $breaks = $filteredBreaks ?? [];
            @endphp

            <div id="breaks-container">
                @foreach($breaks as $index => $break)
                    @php
                        $breakIn = $break['break_in_time'] ?? '';
                        $breakOut = $break['break_out_time'] ?? '';
                    @endphp
                    <div class="admin-attendance-detail__item break-item">
                        <span class="admin-attendance-detail__label">
                            @if ($index === 0)
                                休憩
                            @else
                                休憩{{ $index + 1 }}
                            @endif
                        </span>
                        <div class="admin-attendance-detail__break-wrapper">
                            <input type="text" name="breaks[{{ $index }}][break_in_time]" class="time-input" value="{{ $breakIn }}" placeholder="00:00">
                            <span>~</span>
                            <input type="text" name="breaks[{{ $index }}][break_out_time]" class="time-input" value="{{ $breakOut }}" placeholder="00:00">
                        </div>
                    </div>
                @endforeach
                <div class="admin-attendance-detail__item break-item">
                    <span class="admin-attendance-detail__label">
                        @if (count($breaks) === 0)
                            休憩
                        @else
                            休憩{{ count($breaks) + 1 }}
                        @endif
                    </span>
                    <div class="admin-attendance-detail__break-wrapper">
                        <input type="text" name="breaks[{{ count($breaks) }}][break_in_time]" class="time-input" placeholder="00:00">
                        <span>~</span>
                        <input type="text" name="breaks[{{ count($breaks) }}][break_out_time]" class="time-input" placeholder="00:00">
                    </div>
                </div>
            </div>

            <div class="admin-attendance-detail__item">
                <span class="admin-attendance-detail__label">備考</span>
                <div class="admin-attendance-detail__textarea-wrapper">
                    <textarea class="admin-attendance-detail__textarea" name="note">{{ $attendance->note ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="admin-attendance-detail__actions">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="target_date" value="{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}">
            <button type="submit" class="admin-attendance-detail__button--update">
                修正
            </button>
        </div>
    </form>
</div>

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const breaksContainer = document.getElementById('breaks-container');

        // ページ読み込み時に最初の空の休憩フォームを追加
        if (breaksContainer.children.length === 0) {
            addBreakInput();
        }

        // 休憩時間の入力欄を監視
        breaksContainer.addEventListener('input', function(event) {
            const allBreakItems = breaksContainer.children;
            const currentItemCount = allBreakItems.length;
            const lastBreakItem = allBreakItems[currentItemCount - 1];

            // 新しい入力フォームの追加を管理
            const lastBreakInputs = lastBreakItem.querySelectorAll('.time-input');
            const breakInValue = lastBreakInputs[0].value.trim();
            const breakOutValue = lastBreakInputs[1].value.trim();

            // 最後の休憩入力欄の両方に値が入ったら、新しい休憩フォームを追加
            if (breakInValue !== '' && breakOutValue !== '') {
                addBreakInput();
            }

            // 最後の入力フォームの削除を管理
            if (currentItemCount > 1) { // フォームが複数ある場合のみチェック
                const secondLastBreakItem = allBreakItems[currentItemCount - 2];
                const secondLastBreakInputs = secondLastBreakItem.querySelectorAll('.time-input');
                const secondLastBreakInValue = secondLastBreakInputs[0].value.trim();
                const secondLastBreakOutValue = secondLastBreakInputs[1].value.trim();

                // 最後の入力フォームの一つ前が空になったら、最後のフォームを削除
                if (breakInValue === '' && breakOutValue === '' &&
                    (secondLastBreakInValue === '' || secondLastBreakOutValue === ''))
                {
                    lastBreakItem.remove();
                }
            }
        });

        function addBreakInput() {
            const breakCount = breaksContainer.children.length;
            const labelText = breakCount === 0 ? '休憩' : `休憩${breakCount + 1}`;
            const breakHtml = `
                <div class="admin-attendance-detail__item break-item">
                    <span class="admin-attendance-detail__label">${labelText}</span>
                    <div class="admin-attendance-detail__break-wrapper">
                        <input type="text" name="breaks[${breakCount}][break_in_time]" class="time-input" placeholder="00:00" autocomplete="off">
                        <span>~</span>
                        <input type="text" name="breaks[${breakCount}][break_out_time]" class="time-input" placeholder="00:00" autocomplete="off">
                    </div>
                </div>
            `;
            breaksContainer.insertAdjacentHTML('beforeend', breakHtml);
        }
    });
</script>
@endsection
@endsection
