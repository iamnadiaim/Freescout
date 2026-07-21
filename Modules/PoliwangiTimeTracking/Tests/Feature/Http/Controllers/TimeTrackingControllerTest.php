<?php

namespace Modules\PoliwangiTimeTracking\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeTrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected static $timeTrackingMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$timeTrackingMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiTimeTracking/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$timeTrackingMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app['env'] = 'testing';
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        $this->app->register(\Modules\PoliwangiTimeTracking\Providers\PoliwangiTimeTrackingServiceProvider::class);
    }

    public function test_status_requires_authentication()
    {
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status');
    }

    public function test_status_validation_fails()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create();
        $this->actingAs($user);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_status_conversation_not_found()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create();
        $this->actingAs($user);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', [
            'conversation_id' => 999
        ]);
        
        $response->assertStatus(404);
    }

    public function test_status_authorization_fails()
    {
        $this->withExceptionHandling();
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        $mailbox = factory(Mailbox::class)->create();
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'created_by_user_id' => $user->id
        ]);
        
        // User does not have access to mailbox
        $this->actingAs($user);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', [
            'conversation_id' => $conversation->id
        ]);
        
        // Either 403 or handled by auth policy
        $response->assertStatus(403);
    }

    public function test_status_unassigned_user()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $otherAdmin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        
        // Assigned to otherAdmin
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $otherAdmin->id,
            'created_by_user_id' => $admin->id
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', [
            'conversation_id' => $conversation->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success'
        ]);
        $response->assertJsonFragment([
            'status' => 'unassigned',
            'logged_seconds' => 0,
            'active_seconds' => 0,
            'total_seconds' => 0
        ]);
    }

    public function test_status_unassigned_conversation()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        
        // Not assigned to anyone
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
            'created_by_user_id' => $admin->id
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', [
            'conversation_id' => $conversation->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);
        // Since we are unassigned conversation, anyone can track.
        $this->assertNotEquals('unassigned', $response->json('timer.status'));
    }

    public function test_status_assigned_to_current_user()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        
        // Assigned to admin
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id,
            'created_by_user_id' => $admin->id
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->postJson(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking/status', [
            'conversation_id' => $conversation->id
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);
        $this->assertNotEquals('unassigned', $response->json('timer.status'));
    }

    public function test_can_track_conversation_private_method_unauthenticated()
    {
        $controller = new \Modules\PoliwangiTimeTracking\Http\Controllers\TimeTrackingController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('canTrackConversation');
        $method->setAccessible(true);

        $mailbox = factory(Mailbox::class)->create();
        $user = factory(User::class)->create();
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'created_by_user_id' => $user->id
        ]);
        $this->assertFalse($method->invoke($controller, $conversation));
    }
}
