<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('partials.notification-bell', function ($view) {
            $user = auth()->user();

            $view->with([
                'bellUnread' => $user ? $user->unreadNotifications()->count() : 0,
                'bellItems' => $user ? $user->notifications()->limit(8)->get() : collect(),
            ]);
        });
    }
}
