@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}" />
@endsection

@section('content')

<div class="attendance-detail__container">
    <div class="attendance-detail__header-group">
        <h2 class="attendance-detail__heading">勤怠詳細</h2>
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

    @if ($attendance && $attendance->application?->status == 1)
    {{-- 承認待ちの場合は、入力フォームではなく申請内容をテキストで表示 --}}

    @php
        // 修正フォームもこの変数を利用するため、この位置に移動
        $reasonData = json_decode($attendance->application?->reason, true) ?? [];
        $breaks = $reasonData['breaks'] ?? [];
    @endphp

    <div class="attendance-detail__card">
        <div class="attendance-detail__item">
            <span class="attendance-detail__label">名前</span>
            <div class="attendance-detail__data attendance-detail__data--name">
                <span>{{ Auth::user()->name }}</span>
            </div>
        </div>
        <div class="attendance-detail__item">
            <span class="attendance-detail__label">日付</span>
            <div class="attendance-detail__data attendance-detail__data--date">

                @php
                    $parsedDate = null;
                    try {
                        $parsedDate = \Carbon\Carbon::parse($date);
                    } catch (\Exception $e) {}
                @endphp

                <span>{{ $parsedDate ? $parsedDate->isoFormat('YYYY年') : ($date ?? '日付不明') }}</span>
                <span>{{ $parsedDate ? $parsedDate->isoFormat('M月D日') : '' }}</span>
            </div>
        </div>
        <div class="attendance-detail__item">
            <span class="attendance-detail__label">出勤・退勤</span>
            <div class="attendance-detail__time-wrapper">
                <span>{{ $reasonData['check_in_time'] ?? '未入力' }}</span>
                <span>~</span>
                <span>{{ $reasonData['check_out_time'] ?? '未入力' }}</span>
            </div>
        </div>
        @php
            //休憩データが存在し、かつ入/出のどちらかが入力されている休憩のみをフィルタリング
            $validBreaks = collect($breaks)->filter(function($break) {
                return !empty($break['break_in_time']) || !empty($break['break_out_time']);
            });
        @endphp

        @forelse($validBreaks as $index => $break)
            <div class="attendance-detail__item">
                <span class="attendance-detail__label">
                    @if ($index === 0)
                        休憩
                    @else
                        休憩{{ $index + 1 }}
                    @endif
                </span>
                <div class="attendance-detail__break-wrapper">
                    @php
                        // 変数が連想配列であり、キーが存在するかを確認
                        $breakInTime = $break['break_in_time'] ?? null;
                        $breakOutTime = $break['break_out_time'] ?? null;

                        //dd($breakInTime, gettype($breakInTime));

                        $inTimeDisplay = (!empty($breakInTime) && is_string($breakInTime) && strlen($breakInTime) >= 4)
                            ? \Carbon\Carbon::parse($breakInTime)->format('H:i')
                            : '未入力';

                        $outTimeDisplay = (!empty($breakOutTime) && is_string($breakOutTime) && strlen($breakOutTime) >= 4)
                            ? \Carbon\Carbon::parse($breakOutTime)->format('H:i')
                            : '未入力';

                    @endphp

                    {{-- break_in_timeの表示 --}}
                    <span>{{ $inTimeDisplay }}</span>
                    <span>~</span>

                    {{-- break_out_timeの表示 --}}
                    <span>{{ $outTimeDisplay }}</span>
                </div>
            </div>
        @empty
            <div class="attendance-detail__item">
                <span class="attendance-detail__label">休憩</span>
                <div class="attendance-detail__break-wrapper">
                    <span>休憩記録なし</span>
                </div>
            </div>
        @endforelse
        <div class="attendance-detail__item">
            <span class="attendance-detail__label">備考</span>
            <div class="attendance-detail__textarea-wrapper">
                <span>{{ $reasonData['note'] ?? '備考なし' }}</span>
            </div>
        </div>
    </div>
    <div class="attendance-detail__actions">
        <div class="alert-warning" role="alert">
            ＊承認待ちのため修正できません。
        </div>
    </div>
    @else
    {{-- 修正フォーム部分 --}}
    <form action="{{ route('attendance.update', ['id' => $attendance->id ?? $date]) }}" method="POST" class="attendance-detail__form">
        @csrf
        @method('PUT')

        <div class="attendance-detail__card">
            <div class="attendance-detail__item">
                <span class="attendance-detail__label">名前</span>
                <div class="attendance-detail__data attendance-detail__data--name">
                    <span>{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="attendance-detail__item">
                <span class="attendance-detail__label">日付</span>
                <div class="attendance-detail__data attendance-detail__data--date">
                    <span>{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年') }}</span>
                    <span>{{ \Carbon\Carbon::parse($date)->isoFormat('M月D日') }}</span>
                </div>
            </div>

            <div class="attendance-detail__item">
                <span class="attendance-detail__label">出勤・退勤</span>
                <div class="attendance-detail__time-wrapper">
                    <input type="text" name="check_in_time" class="time-input" value="{{ $attendance && $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '' }}" placeholder="00:00">
                    <span>~</span>
                    <input type="text" name="check_out_time" class="time-input" value="{{ $attendance && $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '' }}" placeholder="00:00">
                </div>
            </div>

            <div id="breaks-container">
            @php
                // 1. DBの確定データを初期値として取得
                // $attendance->breaks が存在しない場合は空のコレクションとする（nullチェック不要にする）
                $breaksCollection = ($attendance && $attendance->relationLoaded('breaks') && $attendance->breaks)
                    ? $attendance->breaks
                    : collect();

                // 2. old()データがあれば、それを優先
                $breaksData = old('breaks');

                // 3. old()がなければ、DBのコレクションを配列に変換して使用
                if (is_null($breaksData)) {
                   // Collectionのmapを使って、モデルオブジェクトをシンプルな配列に変換する
                    // breaksDataはモデルオブジェクトではないことを保証する
                    $breaksData = $breaksCollection->map(function($break) {
                        return [
                            'break_in_time' => $break->break_in_time,
                            'break_out_time' => $break->break_out_time,
                        ];
                    })->toArray();
                }

                // 4. 休憩データがない場合でも、最初の休憩（休憩0）の空データを強制的に作成（JSの初期化に対応）
                if (empty($breaksData)) {
                    $breaksData = [['break_in_time' => null, 'break_out_time' => null]];
                }
            @endphp

            @foreach($breaksData as $index => $break)
                @php
                    //$break が常に配列であることを想定し、直接キーでアクセスする
                    $breakInValue = $break['break_in_time'] ?? null;
                    $breakOutValue = $break['break_out_time'] ?? null;

                    if ($index > 0 && empty($breakInValue) && empty($breakOutValue)) {
                        continue;
                    }

                    // Carbonでフォーマット
                    $breakInTime = !empty($breakInValue) ? \Carbon\Carbon::parse($breakInValue)->format('H:i') : '';
                    $breakOutTime = !empty($breakOutValue) ? \Carbon\Carbon::parse($breakOutValue)->format('H:i') : '';
                @endphp

                <div class="attendance-detail__item break-item" data-index="{{ $index }}">
                    <span class="attendance-detail__label">
                        @if ($index === 0)
                            休憩
                        @else
                            休憩{{ $index + 1 }}
                        @endif
                    </span>
                    <div class="attendance-detail__break-wrapper">
                        <input type="text" name="breaks[{{ $index }}][break_in_time]" class="time-input" value="{{ $breakInTime }}" placeholder="00:00">
                        <span>~</span>
                        <input type="text" name="breaks[{{ $index }}][break_out_time]" class="time-input" value="{{ $breakOutTime }}" placeholder="00:00">
                    </div>
                </div>
            @endforeach
        </div>

            <div class="attendance-detail__item">
                <span class="attendance-detail__label">備考</span>
                <div class="attendance-detail__textarea-wrapper">
                    <textarea class="attendance-detail__textarea" name="note">{{ $attendance->note ?? '' }}</textarea>
                </div>
            </div>
        </div>

            <div class="attendance-detail__actions">
                <input type="hidden" name="target_date" value="{{  \Carbon\Carbon::parse($date)->format('Y-m-d') }}">
                <button type="submit" class="attendance-detail__button--update">修正</button>
            </div>
    </form>
    @endif


@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const breaksContainer = document.getElementById('breaks-container');

        // 休憩入力欄のHTMLを生成する関数
        function addBreakInput(breakCount, breakIn = '', breakOut = '') {
            const labelText = breakCount === 0 ? '休憩' : `休憩${breakCount + 1}`;
            const breakHtml = `
                <div class="attendance-detail__item break-item" data-index="${breakCount}">
                    <span class="attendance-detail__label">${labelText}</span>
                    <div class="attendance-detail__break-wrapper">
                        <input type="text" name="breaks[${breakCount}][break_in_time]" class="time-input" placeholder="00:00" autocomplete="off" value="${breakIn}">
                        <span>~</span>
                        <input type="text" name="breaks[${breakCount}][break_out_time]" class="time-input" placeholder="00:00" autocomplete="off" value="${breakOut}">
                    </div>
                </div>
            `;
            breaksContainer.insertAdjacentHTML('beforeend', breakHtml);
        }

        // --- 初期化処理 ---
        let initialCount = breaksContainer.children.length;

        // Blade側で休憩が1つもレンダリングされていない場合、最初の休憩（休憩1）を常に追加
        if (initialCount === 0) {
            addBreakInput(0);
            initialCount = 1; // カウントを更新
        }

        // 最後の休憩欄が完全に入力済みの場合、次の空欄を一つ追加
        const allBreakItems = Array.from(breaksContainer.children);
        const lastBreakItem = allBreakItems[allBreakItems.length - 1];
        if (lastBreakItem) {
            const lastBreakInputs = lastBreakItem.querySelectorAll('.time-input');
            const breakInValue = lastBreakInputs[0]?.value?.trim() || '';
            const breakOutValue = lastBreakInputs[1]?.value?.trim() || '';

            // 最後の休憩欄が入力済みなら、次の空欄を追加
            if (breakInValue !== '' && breakOutValue !== '') {
                addBreakInput(allBreakItems.length);
            }
        }

        // --- イベントリスナー ---
        breaksContainer.addEventListener('input', function(event) {
            const allItems = Array.from(breaksContainer.children);
            const lastIndex = allItems.length - 1;

            // 最後の休憩欄が入力済みなら次の空欄を追加
            const lastItem = allItems[lastIndex];
            const lastInputs = lastItem.querySelectorAll('.time-input');
            const lastInValue = lastInputs[0]?.value?.trim() || '';
            const lastOutValue = lastInputs[1]?.value?.trim() || '';

            // 1. 新しい休憩欄の追加ロジック
            if (lastInValue !== '' && lastOutValue !== '') {
                 // 次のインデックスの休憩欄がDOMに存在しなければ追加
                if (!breaksContainer.querySelector(`[data-index="${lastIndex + 1}"]`)) {
                    addBreakInput(allItems.length);
                }
            }

            // 2. 休憩欄の削除ロジック（厳格な条件）
            // 休憩が2つ以上ある場合のみ処理
            if (allItems.length > 1) {
                // 直前の休憩欄（次の入力欄の親）
                const secondLastItem = allItems[lastIndex - 1];
                const secondLastInputs = secondLastItem.querySelectorAll('.time-input');
                const secondLastInValue = secondLastInputs[0]?.value?.trim() || '';
                const secondLastOutValue = secondLastInputs[1]?.value?.trim() || '';

                // A. 削除/非表示ロジック: 直前の休憩が未完成（片方空）の場合、最後の休憩欄（空欄）は削除
                if (secondLastInValue === '' || secondLastOutValue === '') {
                    // 最後の休憩欄が空欄の場合のみ削除する (入力途中ではないか確認)
                    if (lastInValue === '' && lastOutValue === '') {
                        lastItem.remove();
                    }
                }

                // B. 連続空欄の削除: 最後の休憩欄が空になったら、その前の休憩欄が空かどうかをチェックし、連続で空欄ができないようにする
                // これにより、ユーザーが休憩をキャンセルしたとき、空欄が1つだけ残る状態を維持する
                if (lastInValue === '' && lastOutValue === '' && lastIndex > 1) {
                    const thirdLastItem = allItems[lastIndex - 2];
                    const thirdLastInputs = thirdLastItem.querySelectorAll('.time-input');
                    const thirdLastInValue = thirdLastInputs[0]?.value?.trim() || '';
                    const thirdLastOutValue = thirdLastInputs[1]?.value?.trim() || '';

                    if (secondLastInValue === '' && secondLastOutValue === '' && thirdLastInValue === '' && thirdLastOutValue === '') {
                        // 最後の要素と、その前の要素の2つが空なら、最後の要素を削除する
                        lastItem.remove();
                    }
                }
            }
        });
    });
</script>
@endsection

@endsection