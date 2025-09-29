<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use App\Listeners\RedirectAfterLogout;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        Logout::class => [
            RedirectAfterLogout::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Handle user logout event.
     *
     * @param  \Illuminate\Auth\Events\Logout  $event
     * @return void|\Illuminate\Http\RedirectResponse
     */
    public static function handleUserLogout($event)
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        return redirect()->route('login');
    }
}
