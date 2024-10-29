<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Resources\Json\JsonResource;

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
        //
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return env('CLIENT_URL'). '/reset-password?email='.$user->email .'&token='. $token;
        });
        JsonResource::withoutWrapping();
    }
}
