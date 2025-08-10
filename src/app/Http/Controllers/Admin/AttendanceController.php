<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
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
        //リクエストから年と月を取得。なければ現在の年月に設定
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        //指定された年月の最初の日と最後の日を取得
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        //指定年月の全スタッフの勤怠記録を取得
        $attendances = Attendance::with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(15);

        //前月と翌月の情報を計算
        $prevMonth = $startDate->copy()->subMonth();
        $nextMonth = $startDate->copy()->addMonth();

        return view('admin.attendances.index', compact('attendances', 'year', 'month', 'prevMonth', 'nextMonth'));
    }

    /**
     * 個別勤怠の詳細画面を表示
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attendances = Attendance::with('user')->findOrFail($id);

        return view('admin.attendances.show', compact('attendance'));
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
        $attendance = Attendance::findOrFail($id);

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
