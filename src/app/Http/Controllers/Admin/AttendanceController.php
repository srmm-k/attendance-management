<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use App\Http\Requests\AttendanceRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * スタッフ全員の勤怠一覧画面を表示
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //リクエストから日付を取得。なければ今日の日付に設定
        $date = $request->input('date', Carbon::today()->toDateString());
        $carbonDate = Carbon::createFromFormat('Y-m-d', $date);

        //指定年月の全スタッフの勤怠記録を取得
        $attendances = Attendance::with('user')
            ->whereDate('date', $carbonDate)
            ->orderBy('check_in_time', 'asc')
            ->paginate(15);

        //前月と翌月の情報を計算
        $prevDay = $carbonDate->copy()->subDay();
        $nextDay = $carbonDate->copy()->addDay();

        return view('admin.attendances.index', compact('attendances', 'carbonDate', 'prevDay', 'nextDay'));
    }

    /**
     * 個別勤怠の詳細画面を表示
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // IDを解析し、user_idとdateを取得するための初期化
    $userId = null;
    $date = null;
    $attendance = null;
    $user = null;

    // 1. IDがカスタム形式 (user_id-YYYY-MM-DD) かどうかをチェック
    if (str_contains($id, '-')) {
        [$userId, $date] = explode('-', $id, 2);

        // ユーザー情報を取得
        $user = User::findOrFail($userId);

        // その日付の勤怠情報をDBから再取得を試みる
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $date)
            ->with(['breaks', 'application'])
            ->first();

    } else {
        // 2. 勤怠IDの形式の場合（登録済みの場合）
        $attendance = Attendance::with(['breaks', 'application', 'user'])->find($id);

        if (!$attendance) {
            abort(404, '指定された勤怠情報が見つかりません。');
        }

            $user = $attendance->user;
            $date = $attendance->date; // 日付を取得
        }


    // 3. 勤怠情報が存在しない場合、新しいインスタンスを作成
    if (!$attendance && $user) {
        $attendance = new Attendance([
            'user_id' => $user->id,
            'date' => $date
        ]);
    }

    // 4. $user が最終的に特定できなかった場合の対応
        if (!$user) {
            abort(404, '関連するユーザー情報が見つかりません。');
        }

        // Blade側でエラーにならないよう、リレーションを保証
        if (!$attendance->relationLoaded('breaks') || $attendance->breaks === null) {
            //break_timesテーブルが存在し、リレーションがある場合はbreaksを保証
            $attendance->setRelation('breaks', collect());
        }
        if (!$attendance->relationLoaded('application')) {
            $attendance->setRelation('application', null);
        }

        $displayCheckIn = $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '';
    $displayCheckOut = $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '';
    $displayBreaks = []; // 表示用の休憩リスト

    $reasonData = $attendance->application ? json_decode($attendance->application->reason, true) : null;

    // 初期値として元の勤怠時刻と休憩リストを設定
    $displayCheckIn = $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '';
    $displayCheckOut = $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '';

    // 元の休憩リストを配列化
    $originalBreaksArray = $attendance->breaks->map(function($break) {
        return [
            'break_in_time' => $break->break_in_time ? \Carbon\Carbon::parse($break->break_in_time)->format('H:i') : '',
            'break_out_time' => $break->break_out_time ? \Carbon\Carbon::parse($break->break_out_time)->format('H:i') : '',
        ];
    })->toArray();

    $displayBreaks = $originalBreaksArray; // 元の休憩リスト（Eloquent Collectionを配列に）

    if ($reasonData && ($attendance->application->status == 1 || $attendance->application->status == 2)) {
        // 申請中の時刻を優先
        $displayCheckIn = $reasonData['check_in_time'] ?? $displayCheckIn;
        $displayCheckOut = $reasonData['check_out_time'] ?? $displayCheckOut;

        // 申請中の休憩リストを優先
        $displayBreaks = $reasonData['breaks'] ?? $displayBreaksArray;
    }

    // 休憩開始・終了の両方が空の要素を削除
    $filteredBreaks = array_filter($displayBreaks, function ($break) {
        $in = $break['break_in_time'] ?? null;
        $out = $break['break_out_time'] ?? null;
        return ($in !== null && $in !== '') || ($out !== null && $out !== '');
    });

    // 配列のインデックスを振り直す
    $filteredBreaks = array_values($filteredBreaks);
    // ビューに渡すデータ
    return view('admin.attendances.show', compact('user', 'attendance', 'date', 'displayCheckIn', 'displayCheckOut', 'filteredBreaks'));
    }

    /**
     * 勤怠記録を更新
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id // 勤怠IDまたはuser_id-YYYY-MM-DD
     * @return \Illuminate\Http\Response
     */
    public function update(AttendanceRequest $request, $id)
    {
        // 1. 勤怠レコードの特定 (ID、カスタム形式、または日付のみ)
        $targetDate = null;
        $userId = null;
        $attendance = null;

        $userId = $request->user_id;

        // $id が日付形式かチェック
        $isDateOnly = false;
        try {
            if (Carbon::createFromFormat('Y-m-d', $id) && strlen($id) === 10) {
                $isDateOnly = true;
                $targetDate = $id;
            }
        } catch (\Exception $e) {
        }

        if ($isDateOnly) {
            // パターン A: 日付のみが渡された場合 (新規登録を試みるパターン)
            $targetDate = $id;

        } elseif (str_contains($id, '-')) {
            // パターン B: カスタム形式 (user_id-YYYY-MM-DD) の場合
            [$dummyUserId, $targetDate] = explode('-', $id, 2);
            // $userId は $request->user_id から取得済み

        } elseif (is_numeric($id) && $id > 0) {
            // パターン C: 勤怠ID（数字）の場合
            $attendance = Attendance::findOrFail($id);
            $userId = $attendance->user_id;
            $targetDate = $attendance->date->toDateString();

        } else {
             // 上記のいずれにも該当しない場合 (不正なID)
            return redirect()->back()->with('error', '勤怠情報の識別子が無効です。');
        }

        // 2. 勤怠レコードを取得または新規作成（firstOrCreate）
        if (!$attendance) {
            $attendance = Attendance::firstOrCreate(
                ['user_id' => $userId, 'date' => $targetDate],
                ['check_in_time' => null, 'check_out_time' => null] // 新規作成時の初期値
            );
        }

        // 3. フォーム値の準備と時刻のパース
        // targetDate を使用して完全な日時を作成
        $fullTargetDate = $targetDate;

        // Carbon::parseにそのまま渡すことで、日付が自動で付与される
        $checkin = $request->check_in_time ? Carbon::parse($request->check_in_time)->format('H:i:s') : null;
        $checkout = $request->check_out_time ? Carbon::parse($request->check_out_time)->format('H:i:s') : null;

        // 4. 休憩時間の処理（BreakTimeテーブルの更新/新規作成）
        $attendance->breaks()->delete();
        $breakTimeTotal = 0;

        // 休憩データが `breaks` 配列として送られてくることを想定
        $breaksData = $request->input('breaks', []);

        foreach ($breaksData as $break) {
            $breakInTime = $break['break_in_time'] ?? null;
            $breakOutTime = $break['break_out_time'] ?? null;

            if ($breakInTime && $breakOutTime) {
                try {
                    // $targetDate を使って Carbon インスタンスを作成
                    $break_in = Carbon::parse($fullTargetDate . ' ' . $breakInTime);
                    $break_out = Carbon::parse($fullTargetDate . ' ' . $breakOutTime);

                    // 時間計算
                    $breakTimeTotal += $break_in->diffInMinutes($break_out);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_in_time' => $break_in,
                        'break_out_time' => $break_out,
                    ]);
                } catch (\Exception $e) {
                    // 無効な時刻データはスキップ
                }
            }
        }

        // 5. 労働時間の合計を計算
        $totalTime = 0;
        if ($checkin && $checkout) {
            // 時刻文字列を Carbon で比較・計算するために、日付を付与してパース
            $checkinCarbon = Carbon::parse($fullTargetDate . ' ' . $checkin);
            $checkoutCarbon = Carbon::parse($fullTargetDate . ' ' . $checkout);
            $totalTime = $checkinCarbon->diffInMinutes($checkoutCarbon) - $breakTimeTotal;
        }

        // 6. 勤怠レコードの更新
        $attendance->update([
            'check_in_time' => $checkin,
            'check_out_time' => $checkout,
            'break_time'  => $breakTimeTotal,
            'total_time' => $totalTime,
            'note' => $request->note,
        ]);

        // 7. 申請レコードの削除 (管理者による直接修正は申請を不要にするため)
        if ($attendance->application) {
            $attendance->application->delete();
        }

        // リダイレクト
        // 修正画面に戻るのが自然。詳細画面にリダイレクト
        return redirect()->route('attendance.show', ['id' => $attendance->id])->with('status', '勤怠情報を管理者として修正・反映しました。');
    }
}
