<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookFactory;
use Illuminate\Http\Request;

class NotificationWebhookTest extends TestCase
{
    use RefreshDatabase;

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
        
        $response->assertStatus(400);
        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['ok']);
        $this->assertEquals('Webhook failed intentionally', $content['message']);
    }
}
