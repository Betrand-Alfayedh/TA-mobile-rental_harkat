<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Daftarkan route broadcasting dengan middleware auth
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Include file channels.php
        require base_path('routes/channels.php');
    }
}
