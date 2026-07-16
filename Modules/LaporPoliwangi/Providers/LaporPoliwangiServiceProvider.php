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
        $viewPathUpper = __DIR__ . '/../Resources/views';
        $viewPathLower = __DIR__ . '/../resources/views';

        $viewPath = is_dir($viewPathUpper) ? $viewPathUpper : $viewPathLower;

        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'laporpoliwangi');
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
