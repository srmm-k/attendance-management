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
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //全ての申請を新しい順で取得
        $applications = Application::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.applications.index', compact('applications'));
    }
    /**
     * 個別の申請詳細（承認・却下）画面を表示
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //申請の詳細データを取得し、ビューを渡すだけ
        $application = Application::with('user')->findOrFail($id);
        return view('admin.applications.show', compact('application'));
    }

    /**
     * 申請のステータスを更新（承認・却下）
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        $request->validate([
            'status' => 'required|in:2,3', //2:承認済み、3:却下
        ]);

        $application->status = $request->status;
        $application->save();

        return redirect()->route('admin.applications')->with('status', '申請を更新しました。');
    }
}
