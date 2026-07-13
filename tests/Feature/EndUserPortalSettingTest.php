<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\LaporPoliwangi\Models\CustomField;
use Modules\LaporPoliwangi\Models\EndUserPortalSetting;

class EndUserPortalSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        \Session::start();

        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withExceptionHandling();
    }

    public function test_index_unauthorized()
    {
        $user = factory(User::class)->create(); // regular user
        $this->actingAs($user);

        $response = $this->get('/lapor-poliwangi/mailboxes/' . $this->mailbox->id . '/end-user-portal');
        $response->assertStatus(403);
    }

    public function test_index_authorized()
    {
        $this->actingAs($this->admin);

        CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Test Field',
            'type' => 'text',
        ]);

        $this->assertDatabaseMissing('end_user_portal_settings', [
            'mailbox_id' => $this->mailbox->id,
        ]);

        $response = $this->get('/lapor-poliwangi/mailboxes/' . $this->mailbox->id . '/end-user-portal');
        
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.setting');

        $this->assertDatabaseHas('end_user_portal_settings', [
            'mailbox_id' => $this->mailbox->id,
            'submit_ticket_title' => 'Submit a Ticket',
        ]);
    }

    public function test_update_unauthorized()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $response = $this->post('/lapor-poliwangi/mailboxes/' . $this->mailbox->id . '/end-user-portal', [
            'submit_ticket_title' => 'New Title',
        ]);
        $response->assertStatus(403);
    }

    public function test_update_validation_fails()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/lapor-poliwangi/mailboxes/' . $this->mailbox->id . '/end-user-portal', [
            'submit_ticket_title' => '', // required
        ]);

        $response->assertSessionHasErrors('submit_ticket_title');
    }

    public function test_update_success()
    {
        $this->actingAs($this->admin);

        $customField = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Test Field',
            'type' => 'text',
        ]);

        $otherMailbox = factory(Mailbox::class)->create();
        $otherCustomField = CustomField::create([
            'mailbox_id' => $otherMailbox->id,
            'name' => 'Other Field',
            'type' => 'text',
        ]);

        $response = $this->post('/lapor-poliwangi/mailboxes/' . $this->mailbox->id . '/end-user-portal', [
            'submit_ticket_title' => 'Updated Title',
            'portal_url' => 'http://test.com/help',
            'custom_fields' => [$customField->id, $otherCustomField->id],
            'subject_field' => '1',
            'footer' => 'Custom Footer',
        ]);

        $response->assertRedirect(route('laporpoliwangi.end_user_portal.setting', $this->mailbox->id));
        $response->assertSessionHas('success', 'End-User Portal settings saved successfully.');

        $setting = EndUserPortalSetting::where('mailbox_id', $this->mailbox->id)->first();
        
        $this->assertEquals('Updated Title', $setting->submit_ticket_title);
        $this->assertEquals('http://test.com/help', $setting->portal_url);
        
        // Assert JSON decode matches or exactly matches string depending on cast, using decoding
        $savedFields = json_decode($setting->custom_fields, true);
        if (!is_array($savedFields)) {
            $savedFields = $setting->custom_fields; // might be array if casted in model
        }
        $this->assertEquals([$customField->id], $savedFields);
        
        $this->assertTrue((bool)$setting->subject_field);
        $this->assertEquals('Custom Footer', $setting->footer);
        $this->assertFalse((bool)$setting->consent_checkbox);
    }
}
