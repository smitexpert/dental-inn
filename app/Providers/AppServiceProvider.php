<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepository::class,
            \App\Repositories\Eloquent\UserEloquentRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->user() ? [
                    'id'         => auth()->user()->id,
                    'name'       => auth()->user()->name,
                    'email'      => auth()->user()->email,
                    'avatar_url' => auth()->user()->getFirstMediaUrl('avatar'),
                    'roles'      => auth()->user()->getRoleNames(),
                    'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => fn () => [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
            ],
        ]);
    }
}
