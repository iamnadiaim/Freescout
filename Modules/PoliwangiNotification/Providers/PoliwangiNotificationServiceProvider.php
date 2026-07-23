<?php

namespace Modules\PoliwangiNotification\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class PoliwangiNotificationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
        $this->registerEventListeners();
    }

    /**
     * Register Event Listeners
     */
    private function registerEventListeners()
    {
        \Event::listen('App\Events\ConversationUserChanged', function ($event) {
            $conversation = $event->conversation;
            // user dari event adalah aktor yang melakukan assign
            $actorUser = $event->user;
            
            // user dari conversation adalah orang yang ditugaskan
            $assignedUser = $conversation->user;

            if (!$conversation || !$assignedUser) {
                return;
            }

            $channels = \Modules\PoliwangiNotification\Models\NotificationChannel::query()
                ->where('is_active', true)
                ->where(function ($query) use ($conversation) {
                    $query->whereNull('mailbox_id')
                        ->orWhere('mailbox_id', $conversation->mailbox_id);
                })
                ->get();

            if ($channels->isEmpty()) {
                return;
            }

            $subject = trim((string) ($conversation->subject ?: 'Tanpa Subjek'));
            $ticketUrl = route('conversations.view', ['id' => $conversation->id]);
            $userName = trim((string) $assignedUser->first_name . ' ' . $assignedUser->last_name);
            $actorName = trim((string) $actorUser->first_name . ' ' . $actorUser->last_name);

            $message = "📌 *Update Status Laporan*\n\n";
            $message .= "Tiket #{$conversation->number} ({$subject}) telah ditugaskan kepada: *{$userName}*\n";
            $message .= "Oleh: {$actorName}\n\n";
            $message .= "Lihat detail: {$ticketUrl}";

            $options = [
                'event' => 'ticket_assigned',
                'conversation_id' => $conversation->id,
                'mailbox_id' => $conversation->mailbox_id,
            ];

            $notificationService = app(\Modules\PoliwangiNotification\Services\Notifications\NotificationService::class);

            foreach ($channels as $channel) {
                try {
                    $notificationService->send($channel, null, $message, $options);
                } catch (\Exception $e) {
                    \Log::error('Gagal mengirim notifikasi update assign', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Modules\PoliwangiNotification\Hooks\SettingsHook::register();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../Config/notification_channels.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'notification_channels');
        }
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('poliwanginotification.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'poliwanginotification'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/poliwanginotification');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/poliwanginotification';
        }, \Config::get('view.paths')), [$sourcePath]), 'poliwanginotification');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    protected function registerRoutes()
    {
        $routesPath = __DIR__ . '/../Http/routes.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }
}
