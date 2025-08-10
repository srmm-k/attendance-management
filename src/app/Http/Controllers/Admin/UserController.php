<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * スタッフ一覧画面を表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //管理者以外の全ユーザーを取得
        $users = User::where('is_admin', false)->get();

        return view('admin.user.staff_list', compact('users'));
    }

    /**
     * スタッフ個人の勤怠一覧画面を表示
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function showAttendances(Request $request, User $user)
    {
        //リクエストから年と月を取得。なければ現在の年月に設定
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        //指定された年月の最初の人最後の日を取得
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        //指定ユーザーの指定年月の勤怠記録を取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(15);

        //前月と翌月の情報を計算
        $prevMonth = $startDate->copy()->subMonth();
        $nextMonth = $startDate->copy()->addMonth();

        return view('admin.users.staff_attendance_list', compact('user', 'attendances', 'year', 'month', 'prevMonth', 'nextMonth'));
    }

    /**
     * スタッフ個人の勤怠データをCSVでエクスポート
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv(Request $request, User $user)
    {
        //リクエストから年と月を取得。なければ現在の年月に設定
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        //指定された年月の最初の日と最後の日を取得
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        //指定ユーザーの指定年月の勤怠記録を取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc') //CSVは日付昇順が一般的
            ->get();

        $fileName = "{$user->name}_{$year}年{$month}月_勤怠データ.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"' ,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');

            //BOM (Byte Order Mark) を追加してExcelでの文字化けを防ぐ
            fwrite($file, "\xEF\xBB\xBF");

            //ヘッダー行
            fputcsv($file, [
                '日付',
                '出勤時間',
                '退勤時間',
                '休憩開始１',
                '休憩終了１',
                '休憩開始２',
                '休憩終了２',
                '合計休憩時間（分）',
                '合計勤務時間（分）',
                '備考'
            ]);

            //データ行
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->date,
                    $attendance->check_in_time,
                    $attendance->check_out_time,
                    $attendance->break_in_time_1,
                    $attendance->break_out_time_1,
                    $attendance->break_in_time_2,
                    $attendance->break_out_time_2,
                    $attendance->break_time,
                    $attendance->total_time,
                    $attendance->note,
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
