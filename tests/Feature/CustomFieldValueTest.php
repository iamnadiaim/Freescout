<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PoliwangiCustomField\Models\CustomField;
use Modules\PoliwangiCustomField\Models\CustomFieldValue;

class CustomFieldValueTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $mailbox;
    protected $conversation;
    protected $routePrefix;

    protected function setUp(): void
    {
        parent::setUp();
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

        $this->routePrefix = \Helper::getSubdirectory() . 'lapor-poliwangi';

        $this->actingAs($this->admin);
        $this->withoutMiddleware();
    }

    public function test_update_custom_field_value_success()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'type_field' => 'text',
            'nama_field' => 'ID Card'
        ]);

        $response = $this->post($this->routePrefix . '/conversations/custom-field-value/update', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $field->id,
            'value' => '123456789'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('custom_field_values', [
            'custom_field_id' => $field->id,
            'conversation_id' => $this->conversation->id,
            'value' => '123456789'
        ]);
    }

    public function test_update_custom_field_value_with_array()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'type_field' => 'multiselect',
            'nama_field' => 'Hobbies',
            'options' => ['Reading', 'Swimming', 'Coding']
        ]);

        $response = $this->post($this->routePrefix . '/conversations/custom-field-value/update', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $field->id,
            'value' => ['Reading', 'Swimming']
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('custom_field_values', [
            'custom_field_id' => $field->id,
            'conversation_id' => $this->conversation->id,
            'value' => json_encode(['Reading', 'Swimming'])
        ]);
    }

    public function test_update_missing_conversation_or_custom_field()
    {
        $response = $this->post($this->routePrefix . '/conversations/custom-field-value/update', [
            // missing both
        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'status' => 'error',
            'msg' => 'Conversation not found.'
        ]);
    }

    public function test_update_custom_field_validation_fails()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'type_field' => 'text',
            'nama_field' => 'Required Field',
            'required' => true
        ]);

        $response = $this->post($this->routePrefix . '/conversations/custom-field-value/update', [
            'conversation_id' => $this->conversation->id,
            'custom_field_id' => $field->id,
            'value' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'status' => 'error',
        ]);
    }
}
