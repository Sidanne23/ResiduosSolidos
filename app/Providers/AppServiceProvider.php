<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Paginator::useTailwind();
        Gate::define('import', fn (User $user) => $user->active && in_array($user->role, ['administrador', 'analista'], true));
        Gate::define('administer', fn (User $user) => $user->active && $user->role === 'administrador');
        Event::listen(Login::class, fn ($event) => Audit::record('auth.login', 'user:'.$event->user->id));
        Event::listen(Logout::class, fn ($event) => Audit::record('auth.logout', 'user:'.$event->user?->id));
    }
}
