<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Users\RequestController;

class RequestController extends Controller
{
    /**
     * 申請一覧画面の表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        //権限チェックと処理の分岐
        if ($user && $user->is_admin) {

            //1.AdminRequestControllerのインスタンスを作成
            $adminController = app(AdminRequestController::class);

            //2.AdminRequestControllerのindexメゾットを実行し、その結果を返す
            return $adminController->index($request);
        }

        $status = $request->query('status', 'pending');

        $query = Application::where('user_id', Auth::id());

        if ($status === 'pending') {
            $query->where('status', 1); //1:承認待ち
        } elseif ($status === 'approved') {
            $query->where('status', 2); //2:承認済み
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('users.applications.index', compact('applications'));
    }

    /**
     * 新しい申請を登録
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'target_date' => 'required|date_format:Y-m-d',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'break_in_time_1' => 'nullable|date_format:H:i',
            'break_out_time_1' => 'nullable|date_format:H:i|after:break_in_time_1',
            'break_in_time_2' => 'nullable|date_format:H:i',
            'break_out_time_2' => 'nullable|date_format:H:i|after:break_in_time_2|after:break_out_time_1',
            'note' => 'nullable|string|max:255',
        ]);

        Application::create([
            'user_id' => Auth::id(),
            'target_date' => Carbon::parse($request->target_date),
            'reason' => json_encode($request->except(['_token', 'target_date'])),
            'status' => 1, //1:承認待ち
        ]);

        return redirect()->route('applications.index')->with('success', '修正申請を送信しました。');
    }

    /**
     * 申請詳細画面の表示
     *
     * @param  \App\Models\Application  $application
     * @return \Illuminate\Http\Response
     */
    public function show(Application $application)
    {
        // ログインユーザーと申請のユーザーが一致するか確認
        if ($application->user_id !== Auth::id()) {
            return redirect()->route('applications.index')->with('error', '他のユーザーの申請は閲覧できません。');
        }

        $dateId = $application->target_date->format('Y-m-d');

        // 申請詳細ビューを返す
        return redirect()->route('attendance.show', ['id' => $dateId]);
    }
}
