<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use Carbon\Carbon;

class RequestController extends Controller
{
    /**
     * 申請一覧画面の表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $application = Application::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('users.requests.index', compact('applications'));
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
            'target_date' => 'required|date',
            'reason' => 'required|string|max:255',
        ]);

        Application::create([
            'user_id' => Auth::id(),
            'target_date' => $request->target_date,
            'reason' => $request->reason,
            'status' => 1, //1:承認待ち
        ]);

        return redirect()->route('application.index')->with('success', '修正申請を送信しました。');
    }
}
