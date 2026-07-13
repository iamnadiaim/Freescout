<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\LaporPoliwangi\Models\NotificationChannel;
use Modules\LaporPoliwangi\Services\Notifications\NotificationService;
use Mockery;

class NotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withExceptionHandling();
    }

    public function test_index_unauthorized()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]); // regular user
        $this->actingAs($user);

        $response = $this->get(route('notification_channels.index'));
        $response->assertStatus(403);
    }

    public function test_index_authorized()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('notification_channels.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
    }

    public function test_store_unauthorized()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]); // regular user
        $this->actingAs($user);

        $response = $this->post(route('notification_channels.store'), [
            'name' => 'Test Channel',
            'type' => 'telegram',
        ]);
        $response->assertStatus(403);
    }

    public function test_store_validation_fails()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('notification_channels.store'), [
            'name' => '', // required
            'type' => 'telegram',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_success()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('notification_channels.store'), [
            'mailbox_id' => $this->mailbox->id,
            'name' => 'My Telegram',
            'type' => 'telegram',
            'bot_token' => '1234:secret',
            'chat_id' => '-100',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_channels', [
            'name' => 'My Telegram',
            'type' => 'telegram',
            'mailbox_id' => $this->mailbox->id,
            'is_active' => 1,
        ]);

        $channel = NotificationChannel::where('name', 'My Telegram')->first();
        $this->assertEquals('1234:secret', $channel->config['bot_token']);
        $this->assertEquals('-100', $channel->config['chat_id']);
    }

    public function test_update_unauthorized()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]); // regular user
        $this->actingAs($user);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Old Name',
            'type' => 'telegram',
            'config' => ['bot_token' => 'old', 'chat_id' => '123'],
            'is_active' => false,
        ]);

        $response = $this->put(route('notification_channels.update', $channel->id), [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_success()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Old Name',
            'type' => 'telegram',
            'config' => ['bot_token' => 'old', 'chat_id' => '123'],
            'is_active' => false,
        ]);

        $response = $this->put(route('notification_channels.update', $channel->id), [
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Updated Name',
            'type' => 'telegram',
            'chat_id' => '456',
            // bot_token omitted, should retain old value
        ]);

        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
        $response->assertSessionHas('success');

        $channel->refresh();
        $this->assertEquals('Updated Name', $channel->name);
        $this->assertEquals($this->mailbox->id, $channel->mailbox_id);
        $this->assertEquals('old', $channel->config['bot_token']); // retained
        $this->assertEquals('456', $channel->config['chat_id']); // updated
    }

    public function test_destroy_success()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'To Delete',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        $response = $this->delete(route('notification_channels.destroy', $channel->id));

        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }

    public function test_toggle_active_ajax()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Toggle Me AJAX',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('notification_channels.toggle_active', $channel->id));
        $response->assertStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals(1, $content['is_active']);

        $channel->refresh();
        $this->assertTrue($channel->is_active);
    }

    public function test_toggle_active_web()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Toggle Me Web',
            'type' => 'telegram',
            'config' => [],
            'is_active' => true,
        ]);

        $response = $this->post(route('notification_channels.toggle_active', $channel->id));
        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
        $response->assertSessionHas('success');

        $channel->refresh();
        $this->assertFalse($channel->is_active);
    }

    public function test_test_channel_fails_inactive()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Inactive Channel',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        $response = $this->post(route('notification_channels.test', $channel->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_test_channel_success()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Active Channel',
            'type' => 'telegram',
            'config' => ['bot_token' => 'dummy', 'chat_id' => '123'],
            'is_active' => true,
        ]);

        // Mock NotificationService without Mockery
        $mockService = new class(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Illuminate\Contracts\Events\Dispatcher::class)
        ) extends NotificationService {
            public function send($channel, $conversation, $message, $options = []) {
                return true;
            }
        };

        $this->app->instance(NotificationService::class, $mockService);

        $response = $this->post(route('notification_channels.test', $channel->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_test_channel_email_success()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Email Channel',
            'type' => 'email',
            'config' => [],
            'is_active' => true,
        ]);

        $mockService = new class(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Illuminate\Contracts\Events\Dispatcher::class)
        ) extends NotificationService {
            public function send($channel, $conversation, $message, $options = []) {
                return true;
            }
        };

        $this->app->instance(NotificationService::class, $mockService);

        $response = $this->post(route('notification_channels.test', $channel->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_test_channel_fails_result_false()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Active Channel',
            'type' => 'telegram',
            'config' => [],
            'is_active' => true,
        ]);

        $mockService = new class(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Illuminate\Contracts\Events\Dispatcher::class)
        ) extends NotificationService {
            public function send($channel, $conversation, $message, $options = []) {
                return false;
            }
        };

        $this->app->instance(NotificationService::class, $mockService);

        $response = $this->post(route('notification_channels.test', $channel->id));
        $response->assertSessionHas('error');
    }

    public function test_test_channel_exception()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Active Channel',
            'type' => 'telegram',
            'config' => [],
            'is_active' => true,
        ]);

        $mockService = new class(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Illuminate\Contracts\Events\Dispatcher::class)
        ) extends NotificationService {
            public function send($channel, $conversation, $message, $options = []) {
                throw new \Exception("Test Send Exception");
            }
        };

        $this->app->instance(NotificationService::class, $mockService);

        $response = $this->post(route('notification_channels.test', $channel->id));
        $response->assertSessionHas('error');
    }

    public function test_webhook_success()
    {
        $request = new \Illuminate\Http\Request();
        
        $mockFactory = new class extends \Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookFactory {
            public function make($type) {
                return new class {
                    public function handle() { return response()->json(['ok' => true]); }
                };
            }
        };
        
        $controller = new \Modules\LaporPoliwangi\Http\Controllers\NotificationChannelController(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationService::class),
            $mockFactory
        );
        
        $response = $controller->webhook($request, 'telegram');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_webhook_exception()
    {
        $request = new \Illuminate\Http\Request();
        
        $mockFactory = new class extends \Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookFactory {
            public function make($type) {
                throw new \Exception("Webhook Error");
            }
        };
        
        $controller = new \Modules\LaporPoliwangi\Http\Controllers\NotificationChannelController(
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class),
            app(\Modules\LaporPoliwangi\Services\Notifications\NotificationService::class),
            $mockFactory
        );
        
        $response = $controller->webhook($request, 'telegram');
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_store_exception()
    {
        $this->actingAs($this->admin);

        NotificationChannel::saving(function () {
            throw new \Exception("Simulated exception");
        });

        $response = $this->post(route('notification_channels.store'), [
            'name' => 'Valid Name',
            'type' => 'telegram',
            'bot_token' => 'token',
            'chat_id' => '123'
        ]);
        $response->assertSessionHas('error');
        
        NotificationChannel::flushEventListeners();
    }

    public function test_authorize_admin_direct_call()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        $this->be($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $controller = app(\Modules\LaporPoliwangi\Http\Controllers\NotificationChannelController::class);
        $controller->index();
    }

    public function test_update_empty_mailbox_and_change_type()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Old Name',
            'type' => 'telegram',
            'config' => ['bot_token' => 'old'],
            'is_active' => false,
        ]);

        $response = $this->put(route('notification_channels.update', $channel->id), [
            'name' => 'New Name',
            'type' => 'whatsapp',
            'mailbox_id' => '', // Trigger `: null`
            'api_url' => 'http://test',
            'api_token' => 'token',
            'phone_number' => '123'
        ]);

        $response->assertRedirect(route('settings', ['section' => 'notification_channels']));
        
        $channel->refresh();
        $this->assertNull($channel->mailbox_id);
        $this->assertEquals('whatsapp', $channel->type);
        $this->assertArrayNotHasKey('bot_token', $channel->config);
    }

    public function test_update_exception()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Old Name',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        NotificationChannel::saving(function () {
            throw new \Exception("Simulated exception");
        });

        $response = $this->put(route('notification_channels.update', $channel->id), [
            'name' => 'New Name',
            'type' => 'telegram',
            'bot_token' => 'token',
            'chat_id' => '123'
        ]);

        $response->assertSessionHas('error');
        NotificationChannel::flushEventListeners();
    }

    public function test_destroy_exception()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'To Delete',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        NotificationChannel::deleting(function () {
            throw new \Exception("Simulated exception");
        });

        $response = $this->delete(route('notification_channels.destroy', $channel->id));
        $response->assertSessionHas('error');
        
        NotificationChannel::flushEventListeners();
    }

    public function test_toggle_active_exception()
    {
        $this->actingAs($this->admin);

        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Toggle Exception',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        NotificationChannel::saving(function () {
            throw new \Exception("Simulated exception");
        });

        $response = $this->post(route('notification_channels.toggle_active', $channel->id));
        $response->assertSessionHas('error');

        // Test AJAX exception
        $responseAjax = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('notification_channels.toggle_active', $channel->id));
        $responseAjax->assertStatus(500);
        $content = json_decode($responseAjax->getContent(), true);
        $this->assertFalse($content['success']);
        
        NotificationChannel::flushEventListeners();
    }

    public function test_store_invalid_type()
    {
        $this->actingAs($this->admin);
        $response = $this->post(route('notification_channels.store'), [
            'name' => 'Invalid',
            'type' => 'invalid_type',
        ]);
        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_config_branches()
    {
        $this->actingAs($this->admin);

        $mockFactory = new class extends \Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory {
            public function supportedTypes() { return ['telegram']; }
            public function rules($type) { return ['bot_token' => 'nullable', 'chat_id' => 'nullable']; }
        };
        $this->app->instance(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class, $mockFactory);

        $response = $this->post(route('notification_channels.store'), [
            'name' => 'Config Branches',
            'type' => 'telegram',
            'bot_token' => ['array_value'], // non-string
            'chat_id' => '', // empty string
        ]);
        
        $response->assertRedirect();
        $channel = NotificationChannel::where('name', 'Config Branches')->first();
        $this->assertIsArray($channel->config['bot_token']);
        $this->assertArrayNotHasKey('chat_id', $channel->config);
    }

    public function test_make_sensitive_fields_optional_branches()
    {
        $this->actingAs($this->admin);
        $channel = NotificationChannel::create([
            'mailbox_id' => null,
            'name' => 'Sensitive Test',
            'type' => 'telegram',
            'config' => [],
            'is_active' => false,
        ]);

        $mockFactory = new class extends \Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory {
            public function supportedTypes() { return ['telegram']; }
            public function rules($type) { 
                return [
                    'bot_token' => ['required', 'string'], // array rules
                    'api_token' => 'nullable|string' // already has nullable
                ]; 
            }
        };
        $this->app->instance(\Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory::class, $mockFactory);

        $response = $this->put(route('notification_channels.update', $channel->id), [
            'name' => 'Update Name',
            'type' => 'telegram',
        ]);

        $response->assertRedirect();
    }
}
