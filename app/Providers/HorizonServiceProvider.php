<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if ((bool) env('HORIZON_ALLOW_PUBLIC', false)) {
                return true;
            }

            $token = request()->bearerToken();
            if (!empty($token)) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken !== null && $accessToken->tokenable !== null) {
                    return true;
                }
            }

            $allowedEmails = array_filter(array_map('trim', explode(',', (string) env('HORIZON_ALLOWED_EMAILS', ''))));
            if (empty($allowedEmails)) {
                return false;
            }

            return in_array((string) optional($user)->email, $allowedEmails, true);
        });
    }
}
