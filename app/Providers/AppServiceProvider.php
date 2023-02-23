<?php

namespace App\Providers;

class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @return void
     */
    public function register() : void
    {
        // override default inotify delays
        config([
            'inotify.delays' => 1,
        ]);
    }
}
