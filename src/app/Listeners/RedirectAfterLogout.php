<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class RedirectAfterLogout
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        //ログアウトしたユーザーが管理者だったかチェック
        if ($event->user && $event->user->is_admin) {
            return redirect()->route('admin.login');
        }
        //それ以外の全てのユーザーを一般ログインページへリダイレクト
        return redirect()->route('login');
    }
}
