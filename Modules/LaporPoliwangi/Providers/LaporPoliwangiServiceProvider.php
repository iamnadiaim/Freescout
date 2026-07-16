<?php

namespace Modules\LaporPoliwangi\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class LaporPoliwangiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $configPath = __DIR__ . '/../config/notification_channels.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'notification_channels');
        }
    }

    public function boot()
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerHooks();
    }

    private function registerRoutes()
    {
        $webRoutesPath = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutesPath)) {
            $this->loadRoutesFrom($webRoutesPath);
        }

        $apiRoutesPath = __DIR__ . '/../routes/api.php';
        if (file_exists($apiRoutesPath)) {
            $this->loadRoutesFrom($apiRoutesPath);
        }
    }

    private function registerViews()
    {
        $viewPathUpper = realpath(__DIR__ . '/../Resources/views');
        $viewPathLower = realpath(__DIR__ . '/../resources/views');

        $paths = [];
        if ($viewPathUpper && is_dir($viewPathUpper)) {
            $paths[] = $viewPathUpper;
        }
        if ($viewPathLower && is_dir($viewPathLower) && !in_array($viewPathLower, $paths)) {
            $paths[] = $viewPathLower;
        }

        if (count($paths) > 0) {
            $this->loadViewsFrom($paths, 'laporpoliwangi');
            
            if ($this->app->bound('view')) {
                foreach ($paths as $path) {
                    $this->app['view']->addLocation($path);
                }
            }

            \Illuminate\Support\Facades\Log::info('LaporPoliwangi views registered successfully', ['paths' => $paths]);
        } else {
            \Illuminate\Support\Facades\Log::error('LaporPoliwangi views directory not found!', [
                'checked_upper' => __DIR__ . '/../Resources/views',
                'checked_lower' => __DIR__ . '/../resources/views',
            ]);
        }
    }

    private function registerHooks()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        $hooks = [
            \Modules\LaporPoliwangi\Hooks\MailboxSettingsHook::class,
            \Modules\LaporPoliwangi\Hooks\AssetHook::class,
            \Modules\LaporPoliwangi\Hooks\SettingsHook::class,
            \Modules\LaporPoliwangi\Hooks\TicketNumberHook::class,
            \Modules\LaporPoliwangi\Hooks\ConversationHook::class,
            \Modules\LaporPoliwangi\Hooks\MenuHook::class,
        ];

        foreach ($hooks as $hookClass) {
            try {
                if (class_exists($hookClass) && method_exists($hookClass, 'register')) {
                    $hookClass::register();
                }
            } catch (\Exception $e) {
                Log::error('LaporPoliwangi hook failed.', [
                    'hook'  => $hookClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
