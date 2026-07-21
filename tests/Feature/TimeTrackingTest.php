<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Conversation;
use App\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user1;
    protected $user2;
    protected $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $this->user1 = factory(User::class)->create(['role' => User::ROLE_USER]);
        $this->user2 = factory(User::class)->create(['role' => User::ROLE_USER]);
        $this->mailbox = factory(Mailbox::class)->create();
        
        $this->mailbox->users()->sync([
            $this->admin->id,
            $this->user1->id
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function createConversation($overrides = [])
    {
        return factory(Conversation::class)->create(array_merge([
            'mailbox_id' => $this->mailbox->id,
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATE_PUBLISHED,
        ], $overrides));
    }

    public function test_status_requires_auth()
    {
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);

        $conversation = $this->createConversation();
        
        $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => $conversation->id
        ]);
    }

    public function test_status_validation_fails()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->actingAs($this->user1);

        $this->postJson(route('PoliwangiPortal.time_tracking.status'), []);
    }

    public function test_status_conversation_not_found()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->actingAs($this->user1);

        $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => 999999
        ]);
    }

    public function test_status_unauthorized_view()
    {
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->actingAs($this->user2);
        
        $conversation = $this->createConversation(['user_id' => $this->user1->id]);
        
        $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => $conversation->id
        ]);
    }

    public function test_status_unassigned_conversation()
    {
        $this->actingAs($this->admin);
        
        $conversation = $this->createConversation(['user_id' => null]);

        $response = $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => $conversation->id
        ]);

        $response->assertStatus(200);
        
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('success', $content['status']);
        $this->assertArrayHasKey('timer', $content);
    }

    public function test_status_assigned_to_other_user()
    {
        $this->actingAs($this->admin);
        
        $conversation = $this->createConversation(['user_id' => $this->user1->id]);

        $response = $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => $conversation->id
        ]);

        $response->assertStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('success', $content['status']);
        $this->assertEquals('unassigned', $content['timer']['status']);
        $this->assertEquals(0, $content['timer']['logged_seconds']);
    }

    public function test_status_assigned_to_me()
    {
        $this->actingAs($this->user1);
        
        $conversation = $this->createConversation(['user_id' => $this->user1->id]);

        $response = $this->postJson(route('PoliwangiPortal.time_tracking.status'), [
            'conversation_id' => $conversation->id
        ]);

        $response->assertStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('success', $content['status']);
        $this->assertArrayHasKey('timer', $content);
    }

    public function test_can_track_conversation_not_logged_in()
    {
        $controller = new \Modules\PoliwangiTimeTracking\Http\Controllers\TimeTrackingController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('canTrackConversation');
        $method->setAccessible(true);
        
        $conversation = $this->createConversation();
        
        $result = $method->invokeArgs($controller, [$conversation]);
        
        $this->assertFalse($result);
    }
}
