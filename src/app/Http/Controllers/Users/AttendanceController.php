<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //ログインユーザーの今日の勤怠情報を取得
        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->first();

        //勤怠登録画面を表示
        return view('users.attendances.create', compact('todayAttendance'));
    }

    /**
     * 勤怠登録（出勤・退勤・休憩）
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //今日の日付を取得
        $today = Carbon::today();
        $user = Auth::User();

        //今日の勤怠記録を取得
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => $today,
        ]);

        $message = '';

        //リクエストされたボタンに応じて処理を分岐
        if ($request->has('checkin')) {
            //出勤ボタンが押された場合
            if (!$attendance->check_in_time) {
                $attendance->check_in_time = Carbon::now();
                $message = '出勤時間を記録しました。';
            } else {
                return redirect()->back()->with('error', '既に出勤済みです。');
            }
        } elseif ($request->has('checout')) {
            //退勤ボタンが押された場合
            if ($attendance->check_in_time && !$attendance->check_out_time) {
                $attendance->check_out_time = Carbon::now();
                $message = '退勤時間を記録しました。';
            } else {
                return redirect()->back()->with('error', '未出勤または既に退勤済みです。');
            }
        } elseif ($request->has('break_in')) {
            //休憩入ボタンが押された場合
            if ($attendance->check_in_time && !attendance->check_out_time) {
                if (!$attendance->break_in_time_1) {
                    $attendance->break_in_time_1 = Carbon::now();
                    $message = '休憩を開始しました。';
                } elseif ($attendance->break_out_time_1 && !$attendance->break_in_time_2) {
                    //１回目の休憩が終了しており、２回目の休憩が開始されていない場合
                    $attendance->break_in_time_2 = Carbon::now();
                    $message = '２回目の休憩を開始しました。';
                } else {
                    return redirect()->back()->with('error', '既に休憩中、または休憩回数の上限です。');
                }
            } else {
                return redirect()->back()->with('error', '出勤していないか、既に退勤済みのため休憩できません。');
            }
        } elseif ($request->has('break_out')) {
            //休憩戻ボタンが押された場合
            if ($attendance->break_in_time_2 && !$attendance->break_out_time_2) {
                //２回目の休憩中
                $attendance->break_out_time_2 = Carbon::now();
                $message = '２回目の休憩を終了しました。';
            } elseif ($attendance->break_in_time_1 && !$attendance->break_out_time_1) {
                //１回目の休憩中
                $attendance->break_out_time_1 = Carbon::now();
                $message = '休憩を終了しました。';
            } else {
                return redirect()->back()->with('error', '休憩を開始していません。');
            }
        } else {
            return redirect()->back()->with('error', '不正な操作です。');
        }

        //各種時間の計算
        $breakTimeTotal = 0;
        if ($attendance->break_in_time_1 && $attendance->break_out_time_1) {
            $break1_in = Carbon::parse($attendance->break_in_time_1);
            $break1_out = Carbon::parse($attendance->break_out_time_1);
            $breakTimeTotal += $break1_in->diffInMinutes($break1_out);
        }
        if ($attendance->break_in_time_2 && $attendance->break_out_time_2) {
            $break2_in = Carbon::parse($attendance->break_in_time_2);
            $break2_out = Carbon::parse($attendance->break_out_time_2);
            $breakTimeTotal += $break2_in->diffInMinutes($break2_out);
        }
        $attendance->break_time = $breakTimeTotal; //合計休憩時間を更新

        $totalTime = 0;
        if($attendance->check_in_time && $attendance->check_out_time) {
            $checkin = Carbon::parse($attendance->check_in_time);
            $checkout = Carbon::parse($attendance->check_out_time);
            $totalTime = $checkin->diffInMinutes($checkout) - $breakTimeTotal;
        }
        $attendance->total_time = $totalTime; //合計勤務時間を更新

        $attendance->save();

        return redirect()->back()->with('status', $message);
        }


    /**
     * 勤怠一覧画面を表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //リクエストから年と月を取得。なければ現在の年月に設定
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        //指定された年月の最初の日と最後の日を取得
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        //ログインユーザーの指定年月の勤怠記録を取得
        $attendances = Attendance::where('user_id', Auth::id())
        ->orderBy('date', 'desc')
        ->paginate(15); //ページネーション

        //前月と翌月の情報を計算
        $prevMonth = $startDate->copy()->subMonth();
        $nextMonth = $startDate->copy()->addMonth();

        return view('users.attendances.index', compact('attendances', 'year', 'month', 'prevMonth', 'nextMonth'));
    }

    /**
     * 勤怠詳細画面を表示
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //ログインユーザーに紐づく勤怠記録のみを取得
        $attendance = Attendance ::where('user_id', Auth::id())->findOrFail($id);

        return view('users.attendances.show', compact('attendance'));
    }

        /**
     * 勤怠記録を更新
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'break_in_time_1' => 'nullable|date_format:H:i',
            'break_out_time_1' => 'nullable|date_format:H:i|after:break_in_time_1',
            'break_in_time_2' => 'nullable|date_format:H:i',
            'break_out_time_2' => 'nullable|date_format:H:i|after:break_in_time_2|after:break_out_time_1',
            'note' => 'nullable|string|max:255',
        ]);

        $checkin = $request->check_in_time ? Carbon::parse($attendance->date . ' ' . $request->check_in_time) : null;
        $checkout = $request->check_out_time ? Carbon::parse($attendance->date . ' ' . $request->check_out_time) : null;

        //休憩時間の合計を計算
        $breakTimeTotal = 0;
        if ($request->break_in_time_1 && $request->break_out_time_1) {
            $break1_in = Carbon::parse($request->break_in_time_1);
            $break1_out = Carbon::parse($request->break_out_time_1);
            $breakTimeTotal += $break1_in->diffInMinutes($break1_out);
        }
        if ($request->break_in_time_2 && $request->break_out_time_2) {
            $break2_in = Carbon::parse($request->break_in_time_2);
            $break2_out = Carbon::parse($request->break_out_time_2);
            $breakTimeTotal += $break2_in->diffInMinutes($break2_out);
        }

        //労働時間の合計を計算
        $totalTime = 0;
        if ($checkin && $checkout) {
            $totalTime = $checkin->diffInMinutes($checkout) - $breakTimeTotal;
        }

        $attendance->update([
            'check_in_time' => $checkin,
            'check_out_time' => $checkout,
            'break_in_time_1' => $request->break_in_time_1,
            'break_out_time_1' => $request->break_out_time_1,
            'break_in_time_2' => $request->break_in_time_2,
            'break_out_time_2' => $request->break_out_time_2,
            'break_time'  => $breakTimeTotal,
            'total_time' => $totalTime,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('status', '勤怠情報を修正しました。');
    }
}
