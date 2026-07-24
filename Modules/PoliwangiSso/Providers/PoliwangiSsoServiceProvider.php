<?php

namespace Modules\PoliwangiSso\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Event;

class PoliwangiSsoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Daftarkan rute web khusus untuk modul ini
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Menyuntikkan tombol login ke tampilan login FreeScout
        Event::listen('view.auth.login.buttons', function() {
            echo '<div class="form-group margin-top-10">
                    <a href="' . route('poliwangisso.redirect') . '" class="btn btn-primary btn-block">
                        <strong>Login dengan SSO Poliwangi</strong>
                    </a>
                  </div>';
        });
        
        \Modules\PoliwangiSso\Hooks\PortalAuthHook::register();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'poliwangisso'
        );
    }
}
