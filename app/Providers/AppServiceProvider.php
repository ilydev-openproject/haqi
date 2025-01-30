<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Listeners\UpdateUserOnlineStatus;
use App\Listeners\UpdateUserOfflineStatus;
use App\Listeners\UpdateLastActivityOnLogin;
use App\Listeners\UpdateLastActivityOnLogout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            Login::class,
            UpdateUserOnlineStatus::class,
        );

        Event::listen(
            Logout::class,
            UpdateUserOfflineStatus::class,
        );

        Event::listen(
            Login::class,
            UpdateLastActivityOnLogin::class,
        );

        Event::listen(
            Logout::class,
            UpdateLastActivityOnLogout::class,
        );
    }
}
