<?php

namespace Modules\PoliwangiCustomField\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\PoliwangiCustomField\Models\CustomField;
use Modules\PoliwangiCustomField\Models\CustomFieldValue;

class ConversationCustomFieldTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $mailbox;
    protected $conversation;
    protected $field;

    protected static $customFieldMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$customFieldMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiCustomField/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$customFieldMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register the module service provider to ensure routes and views are loaded
        $this->app->register(\Modules\PoliwangiCustomField\Providers\PoliwangiCustomFieldServiceProvider::class);

        \Session::start();

        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();

        $this->conversation = factory(Conversation::class)->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATUS_ACTIVE,
        ]);

        $this->field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'Status',
            'type_field' => 'text',
        ]);

        Route::post(
            '/test/conversation-custom-field/update',
            '\Modules\PoliwangiCustomField\Http\Controllers\ConversationCustomFieldController@update'
        );

        $this->actingAs($this->admin);
        $this->withoutMiddleware();
    }

    public function test_conversation_not_found()
    {
        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => 999999,
                'custom_field_id' => $this->field->id,
                'value' => 'Test',
            ]
        );

        $response->assertStatus(404);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('error', $json['status']);
        $this->assertEquals('Conversation not found.', $json['msg']);
    }

    public function test_custom_field_not_found()
    {
        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => 999999,
                'value' => 'Test',
            ]
        );

        $response->assertStatus(404);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('error', $json['status']);
        $this->assertEquals('Custom field not found.', $json['msg']);
    }

    public function test_mailbox_mismatch()
    {
        $otherMailbox = factory(Mailbox::class)->create();

        $field = CustomField::create([
            'mailbox_id' => $otherMailbox->id,
            'nama_field' => 'Priority',
            'type_field' => 'text',
        ]);

        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $field->id,
                'value' => 'Test',
            ]
        );

        $response->assertStatus(422);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('error', $json['status']);
        $this->assertEquals('Custom field does not belong to this mailbox.', $json['msg']);
    }

    public function test_update_success()
    {
        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $this->field->id,
                'value' => 'Completed',
            ]
        );

        $response->assertStatus(200);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('success', $json['status']);
        $this->assertEquals('Custom field updated successfully.', $json['msg']);

        $this->assertDatabaseHas('custom_field_values', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $this->field->id,
            'value' => 'Completed',
        ]);
    }

    public function test_update_success_with_array_value()
    {
        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $this->field->id,
                'value' => ['A', 'B'],
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('custom_field_values', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $this->field->id,
            'value' => json_encode(['A', 'B']),
        ]);
    }

    public function test_update_existing_value()
    {
        CustomFieldValue::create([
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $this->field->id,
            'value' => 'Old',
        ]);

        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $this->field->id,
                'value' => 'New',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('custom_field_values', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $this->field->id,
            'value' => 'New',
        ]);
    }

    public function test_unauthorized_user()
    {
        $user = factory(User::class)->create(); // Regular user
        $this->actingAs($user);

        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $this->field->id,
                'value' => 'Test',
            ]
        );

        $response->assertStatus(403);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('error', $json['status']);
        $this->assertEquals(__('Not enough permissions'), $json['msg']);
    }

    public function test_validation_fails()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'Required Field',
            'type_field' => 'text',
            'required' => true,
        ]);

        $response = $this->postJson(
            '/test/conversation-custom-field/update',
            [
                'conversation_id' => $this->conversation->id,
                'custom_field_id' => $field->id,
                'value' => '', // Empty value for a required field
            ]
        );

        $response->assertStatus(422);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals('error', $json['status']);
    }
}