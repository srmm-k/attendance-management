<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Application;
use App\Http\Requests\AttendanceRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Throwable;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示
     * * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //ログインユーザーの今日の勤怠情報を取得
        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        if (empty($todayAttendance)) {
            $todayAttendance = new Attendance();
        }

        //勤怠登録画面を表示
        return view('users.attendances.create', compact('todayAttendance'));
    }

    /**
     * 勤怠登録（出勤・退勤・休憩）
     * * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        //今日の日付を取得
        $today = Carbon::today();
        $user = Auth::User();

        if (!$user) {
            return redirect()->back()->with('error', 'ユーザーが認証されていません。');
        }

        //今日の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('breaks')
            ->first();

        $message = '';

        //リクエストされたボタンに応じて処理を分岐
        if ($request->has('checkin')) {
            //出勤ボタンが押された場合
            if (!$attendance) { //勤怠レコードが存在しない場合は新規作成
                $attendance = new Attendance();
                $attendance->user_id = $user->id;
                $attendance->date = $today;
                $attendance->check_in_time = Carbon::now()->format('H:i:s');
                $attendance->save();
                $message = '出勤時間を記録しました。';
            } else {
                    return redirect()->back()->with('error', '既に出勤済みです。');
                }
        } elseif ($request->has('checkout')) {
            //退勤ボタンが押された時
            if ($attendance->check_in_time && !$attendance->check_out_time) {
                //現在休憩中ではないことを確認
                $latestBreak = $attendance->breaks->last();
                if ($latestBreak && empty($latestBreak->break_out_time)) {
                    return redirect()->back()->with('error', '休憩を終了してから退勤してください。');
                }

                $attendance->check_out_time = Carbon::now()->format('H:i:s');
                $message = '退勤時間を記録しました。';
            } else {
                return redirect()->back()->with('error', '未出勤または既に退勤済みです。');
            }

        } elseif ($request->has('break_in')) {
            //休憩入ボタンが押された場合
            if ($attendance->check_in_time && !$attendance->check_out_time) {
                //すでに休憩中ではないことを確認
                $latestBreak = $attendance->breaks->last();
                if ($latestBreak && empty($latestBreak->break_out_time)) {
                    return redirect()->back()->with('error', 'すでに休憩中です。');
                }

                //勤怠レコードが存在しない場合、休憩開始前に作成
                if (!$attendance) {
                    $attendance = new Attendance();
                    $attendance->user_id = $user->id;
                    $attendance->date = $today;
                    $attendance->save();
                }

                $break = new BreakTime();
                $break->attendance_id = $attendance->id;
                $break->break_in_time = Carbon::now();
                $break->save();
                return redirect()->back()->with('status', '休憩を開始しました。');
            } else {
                return redirect()->back()->with('error', '出勤していないか、既に退勤済みのため休憩できません。');
            }
        } elseif ($request->has('break_out')) {
            //休憩戻ボタンが押された場合
            if ($attendance->check_in_time && !$attendance->check_out_time) {
                $latestBreak = $attendance->breaks->last();
                if ($latestBreak && empty($latestBreak->break_out_time)) {
                    $latestBreak->break_out_time = Carbon::now();
                    $latestBreak->save();
                    return redirect()->back()->with('status', '休憩を終了しました。');
            } else {
                return redirect()->back()->with('error', '休憩を開始していません。');
            }
        } else {
            return redirect()->back()->with('error', '出勤していないか、既に退勤済みです。');
            }
        } else {
            return redirect()->back()->with('error', '不正な操作です。');
            }

        //各種時間の計算
        $breakTimeTotal = 0;
        if ($attendance->breaks) {
            foreach ($attendance->breaks as $breakTime) {
                if ($breakTime->break_in_time && $breakTime->break_out_time) {
                    $breakIn = Carbon::parse($breakTime->break_in_time);
                    $breakOut = Carbon::parse($breakTime->break_out_time);
                    $breakTimeTotal += $breakIn->diffInMinutes($breakOut);
                    }
                }
            }

        $attendance->break_time = $breakTimeTotal; //合計休憩時間を更新

        $totalTime = 0;
        if($attendance->check_in_time && $attendance->check_out_time) {
            $checkin = Carbon::parse($attendance->check_in_time);
            $checkout = Carbon::parse($attendance->check_out_time);
            $totalTime = $checkin->diffInMinutes($checkout) - $breakTimeTotal;
        }
        $attendance->total_time = $totalTime; //合計勤務時間を更新


        try {

            $attendance->save();

        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'データの保存に失敗しました: ' . $e->getMessage());
        }

        return redirect()->back()->with('status', $message);
        }


    /**
     * 勤怠一覧画面を表示
     * * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $year = $request->input('year') ?? now()->year;
        $month = $request->input('month') ?? now()->month;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // その月の全ての日付を生成
        $daysInMonth = CarbonPeriod::create($startDate, '1 day', $endDate);

        // データベースから勤怠データを取得
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
            ->get();

        // 日付をキーとして勤怠データを整理
        $organizedAttendances = $attendances->keyBy(function($item) {
            return \Carbon\Carbon::parse($item->date)->toDateString();
        });

        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $nextMonth = Carbon::create($year, $month, 1)->addMonth();

        return view('users.attendances.index', compact(
            'daysInMonth', 'organizedAttendances', 'year', 'month', 'prevMonth', 'nextMonth'
        ));
    }

    /**
     * 勤怠詳細画面を表示
     * * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        $user = Auth::user();

    // 管理者の場合はAdminAttendanceControllerに処理を譲渡
    if ($user && $user->is_admin) {
        $adminController = app(AdminAttendanceController::class);
        return $adminController->show($id);
    }

    // 一般ユーザーの処理
    $attendance = null;
    $date = null;

    try {
        $date = Carbon::createFromFormat('Y-m-d', $id)->format('Y-m-d');
    } catch (\Exception $e) {
        $date = null;
    }

    if ($date) {
        // 1. $id が日付形式の場合の処理
        // ログインユーザーのその日の勤怠登録を取得
        $attendance = $user->attendances()
            ->with(['breaks', 'application'])
            ->whereDate('date', $date)
            ->first();
    } else {
        // 2. $id が勤怠IDであると判断された場合の処理
        // ログインユーザーに紐づく勤怠をIDで取得
        $attendance = $user->attendances()
            ->with(['breaks', 'application'])
            ->where('id', $id)
            ->firstOrFail();
        
        $date = $attendance->date;
    }

    // 3. 勤怠情報が存在しない場合は、新規登録用に空のインスタンスを作成
    if (!$attendance || ($date && !$attendance->exists)) {
        // 未登録日を特定した場合は、新しいインスタンスを作成
        $attendance = new Attendance([
            'user_id' => $user->id,
            'date' => $date
        ]);
    }

    // blade側でエラーにならないように、リレーションを保証
    if (!$attendance->relationLoaded('breaks') || !$attendance->breaks) {
        $attendance->setRelation('breaks', collect());
    }
    if (!$attendance->relationLoaded('application') || !$attendance->application) {
        $attendance->setRelation('application', null);
    }

    return view('users.attendances.show', compact('attendance', 'date', 'id'));
    }


        /**
     * 勤怠記録を更新
     * * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function update(AttendanceRequest $request, string $id)
    {
        $user = Auth::user();

        //管理者の場合はAdminAttendanceControllerに処理を譲渡
        if ($user && $user->is_admin) {
            $adminController = app(AdminAttendanceController::class);
            return $adminController->update($request, $id); // AdminControllerに処理を委譲
        }

        //一般ユーザーの処理

        // showメソッドと同様に、日付を特定
        $targetDate = null;
        $isDate = Carbon::createFromFormat('Y-m-d', $id);

        if ($isDate !== false && $isDate->format('Y-m-d') === $id) {
            $targetDate = $id;
        } else {
            // 勤怠IDから日付を取得
            $attendance = $user->attendances()->where('id', $id)->firstOrFail();
            $targetDate = $attendance->date;
        }

        // 勤怠レコードを firstOrCreate で取得または作成
        $attendance = Attendance::firstOrCreate([
            'user_id' => $user->id,
            'date' => $targetDate, // 特定した日付を使用
        ]);

        // 申請データ（フォームデータ＋勤怠ID）をJSON形式で準備
        // Noteとbreaks情報を含むフォームデータを取得
        $applicationDataToSave = $request->except(['_token', '_method']);
        $applicationDataToSave['attendance_id'] = $attendance->id;

        // フォームから送られた時刻データをクリーンアップし、フォーマットを統一する関数
    $cleanTime = function ($time) {
        if (is_null($time) || $time === '' || $time === '0') {
            return null;
        }

        //文字列であることを保証
        $time = (string) $time;

        try {
            return Carbon::createFromFormat('H:i', $time)->format('H:i');
        } catch (Throwable $e) {
            return null; // 不正な形式の場合はnullにする (データ破損防止)
        }
    };

    // 1. 出勤・退勤時刻をクリーンアップ
    $applicationDataToSave['check_in_time'] = $cleanTime($request->input('check_in_time'));
    $applicationDataToSave['check_out_time'] = $cleanTime($request->input('check_out_time'));
    
    // 2. 休憩時刻をクリーンアップ
    $breaks = $request->input('breaks', []);
    foreach ($breaks as $index => $break) {
        $breaks[$index]['break_in_time'] = $cleanTime($break['break_in_time'] ?? null);
        $breaks[$index]['break_out_time'] = $cleanTime($break['break_out_time'] ?? null);
    }
    $applicationDataToSave['breaks'] = $breaks;

        // 既存の申請レコードを検索するか、新規作成
        $application = Application::firstOrNew([
            'attendance_id' => $attendance->id,
        ]);
        
        // 申請内容を更新
        $application->user_id = $user->id;
        $application->attendance_id = $attendance->id;
        $application->target_date = $targetDate;
        $application->status = 1; // 承認待ちに設定
        $application->reason = json_encode($applicationDataToSave); // JSON化して保存
        
        $application->save();

        // 最新の申請情報をリロード
        $attendance->load('application'); 

    return redirect()->route('attendance.show', ['id' => $targetDate])->with('status', '勤怠情報を修正申請しました。');
    }
        /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}