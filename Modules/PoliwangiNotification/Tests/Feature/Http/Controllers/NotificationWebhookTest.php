<?php

namespace Modules\PoliwangiNotification\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PoliwangiNotification\Services\Notifications\NotificationWebhookFactory;
use Illuminate\Http\Request;

class NotificationWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected static $notificationMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$notificationMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiNotification/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$notificationMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app->register(\Modules\PoliwangiNotification\Providers\PoliwangiNotificationServiceProvider::class);
    }

    public function test_handle_success()
    {
        $this->withoutMiddleware();

        $mockFactory = new class extends NotificationWebhookFactory {
            public function make($type) {
                return new class {
                    public function handle(Request $request) {
                        return response()->json(['ok' => true, 'data' => 'success']);
                    }
                };
            }
        };
        $this->app->instance(NotificationWebhookFactory::class, $mockFactory);

        $response = $this->post(route('notification_channels.webhook', ['type' => 'telegram']));
        
        $response->assertStatus(200);
        
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['ok']);
        $this->assertEquals('success', $content['data']);
    }

    public function test_handle_exception()
    {
        $this->withoutMiddleware();

        $mockFactory = new class extends NotificationWebhookFactory {
            public function make($type) {
                throw new \Exception("Webhook failed intentionally");
            }
        };
        $this->app->instance(NotificationWebhookFactory::class, $mockFactory);

        $response = $this->post(route('notification_channels.webhook', ['type' => 'telegram']));
        
        $response->assertStatus(500);
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['ok']);
        $this->assertEquals('Webhook gagal diproses.', $content['error']);
    }
}
