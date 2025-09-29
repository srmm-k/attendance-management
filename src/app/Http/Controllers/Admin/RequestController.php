<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\application;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RequestController extends Controller
{
    /**
     * 全スタッフの申請一覧画面を表示
     **@param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //クエリパラメータからstatusを取得。デフォルトは'pending'
        $status = $request->query('status', 'pending');

        //Eloquentクエリを開始
        $query = Application::with('user');

        //statusパラメータに応じてクエリをフィルタリング
        if ($status === 'approved') {
            $query->where('status', 2); //承認済み
        }else {
            $query->where('status', 1); //承認待ち（デフォルト）
        }

        //フィルタリングした結果をページネーションして取得
        $applications = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.applications.index', compact('applications'));
    }
    /**
     * 個別の申請詳細（承認）画面を表示
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Application $attendance_correct_request)
    {

        $application = $attendance_correct_request;

        //申請の詳細データを取得し、ビューを渡すだけ
        return view('admin.applications.approval', compact('application'));
    }

    /**
     * 申請のステータスを更新（承認）
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Application $attendance_correct_request)
    {
        $application = $attendance_correct_request;

    $request->validate([
        'status' => 'required|in:2', // 承認
    ]);

    $application->status = $request->status;

    // 承認の場合、勤怠詳細を更新
    if ($application->status == 2) {
        $reasonData = json_decode($application->reason, true);
        $attendance = $application->attendance;

        if ($attendance) {
            // 出勤・退勤時間、備考を更新
            $attendance->check_in_time = $reasonData['check_in_time'];
            $attendance->check_out_time = $reasonData['check_out_time'];
            $attendance->note = $reasonData['note'];

            // 既存の休憩データを全て削除
            $attendance->breaks()->delete();

            // 修正された休憩データを再登録
            if (isset($reasonData['breaks']) && is_array($reasonData['breaks'])) {
                foreach ($reasonData['breaks'] as $breakData) {
                    $attendance->breaks()->create([
                        'break_in_time' => $breakData['break_in_time'],
                        'break_out_time' => $breakData['break_out_time'],
                    ]);
                }
            }

            // 合計休憩時間と合計勤務時間を再計算
            $breakTimeTotal = 0;
            if ($attendance->breaks->count() > 0) {
                foreach ($attendance->breaks as $break) {
                    $breakIn = Carbon::parse($break->break_in_time);
                    $breakOut = Carbon::parse($break->break_out_time);
                    $breakTimeTotal += $breakIn->diffInMinutes($breakOut);
                }
            }

            $totalTime = 0;
            if ($attendance->check_in_time && $attendance->check_out_time) {
                $checkin = Carbon::parse($attendance->check_in_time);
                $checkout = Carbon::parse($attendance->check_out_time);
                // `abs()`関数で差分を絶対値にすることで、常に正しい勤務時間を計算
                $totalTime = abs($checkin->diffInMinutes($checkout)) - $breakTimeTotal;
            }

            $attendance->break_time = $breakTimeTotal;
            $attendance->total_time = $totalTime;
            
            $attendance->save();
        }
    }

    $application->save();

    return redirect()->route('applications.index')->with('status', '申請を承認しました。');
}
}
