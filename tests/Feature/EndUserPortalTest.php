<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Mailbox;
use Modules\LaporPoliwangi\Models\EndUserPortalSetting;
use Modules\LaporPoliwangi\Models\CustomField;
use App\Conversation;
use App\Customer;
use App\Email;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\LaporPoliwangi\Models\EndUserPortalAccount;

class EndUserPortalTest extends TestCase
{
    use RefreshDatabase;

    protected $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Option::$cache = [];
        
        $this->mailbox = Mailbox::create([
            'name' => 'Lapor Poliwangi Helpdesk',
            'email' => 'helpdesk@lapor.poliwangi'
        ]);
        
        EndUserPortalSetting::create([
            'mailbox_id' => $this->mailbox->id,
            'portal_url' => url('/help'),
            'submit_ticket_title' => 'Submit a Ticket'
        ]);
    }

    // ==========================================
    // PHASE 1: Portal Display & Auth Basics
    // ==========================================

    public function test_select_auth_displays_auth_selection()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.auth_select'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.auth_select');
    }

    public function test_select_mailbox_redirects_to_show_portal()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.select_mailbox'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.submit_ticket');
    }

    public function test_show_portal_with_mailbox_id()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.submit_ticket', ['mailbox_id' => $this->mailbox->id]));

        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.submit_ticket');
        $response->assertViewHas('mailbox');
    }

    public function test_show_portal_with_custom_fields()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'type' => 'text',
            'name' => 'Student ID'
        ]);

        $setting = EndUserPortalSetting::firstOrCreate(
            ['mailbox_id' => $this->mailbox->id],
            ['custom_fields' => json_encode([$field->id])]
        );
        $setting->update(['custom_fields' => json_encode([$field->id])]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.submit_ticket', ['mailbox_id' => $this->mailbox->id]));

        $response->assertStatus(200);
        $response->assertViewHas('customFieldsByMailbox');
        $customFields = $response->original->getData()['customFieldsByMailbox'];
        
        $this->assertTrue($customFields->has($this->mailbox->id));
        $this->assertEquals($field->id, $customFields[$this->mailbox->id]->first()->id);
    }

    // ==========================================
    // PHASE 2: Ticket Submission
    // ==========================================

    public function test_submit_ticket_validation_fails()
    {
        $this->withExceptionHandling();

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            // empty payload
        ]);

        // Validation should fail and redirect back
        $response->assertRedirect();
        $response->assertSessionHasErrors(['message']);
    }

    public function test_submit_ticket_anonymous_success()
    {
        Event::fake(); // Prevent actual emails/notifications

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'email' => '',
            'name' => '',
            'subject' => 'Help with login',
            'message' => 'I cannot login to my account.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('secret_tracking_code');

        // Check if ticket was created
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Help with login',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $conversation = Conversation::where('subject', 'Help with login')->first();
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'I cannot login to my account.',
            'type' => \App\Thread::TYPE_CUSTOMER,
        ]);
    }
    
    public function test_submit_ticket_with_custom_fields()
    {
        Event::fake();

        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'type' => 'text',
            'name' => 'Student ID'
        ]);

        $setting = EndUserPortalSetting::firstOrCreate(
            ['mailbox_id' => $this->mailbox->id],
            ['custom_fields' => json_encode([$field->id]), 'subject_field' => true]
        );
        $setting->update(['custom_fields' => json_encode([$field->id]), 'subject_field' => true]);


        try {
            $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
                'email' => '',
                'name' => '',
                'subject' => 'Request ID',
                'message' => 'Please provide me an ID.',
                'custom_fields' => [
                    $field->id => '123456789'
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            dump($e->errors());
            throw $e;
        }

        $response->assertRedirect();
        
        $conversation = Conversation::where('subject', 'Request ID')->first();
        
        $this->assertDatabaseHas('custom_field_values', [
            'conversation_id' => $conversation->id,
            'custom_field_id' => $field->id,
            'value' => '123456789'
        ]);
    }

    // ==========================================
    // PHASE 3: Authentication
    // ==========================================

    public function test_login_end_user_displays_form()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.login_end_user'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.login_end_user');
    }

    public function test_login_end_user_submit_invalid_credentials()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_end_user_submit_success()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'End',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $emailRowId = \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'enduser@example.com',
            'customer_id' => $customerId,
        ]);

        EndUserPortalAccount::create([
            'customer_id' => $customerId,
            'email_id' => $emailRowId,
            'auth_type' => 'password',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'enduser@example.com',
            'password' => 'password123',
            'redirect' => '/help'
        ]);

        $response->assertRedirect();
        $this->assertEquals('enduser@example.com', session('end_user_portal_email'));
        $this->assertEquals($customerId, session('end_user_portal_customer_id'));
    }

    public function test_logout_end_user()
    {
        session([
            'end_user_portal_email' => 'enduser@example.com',
            'end_user_portal_customer_id' => 999
        ]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.logout', ['redirect' => '/help']));

        $response->assertRedirect('/help');
        $this->assertNull(session('end_user_portal_email'));
        $this->assertNull(session('end_user_portal_customer_id'));
        $response->assertSessionHas('success');
    }

    public function test_login_end_user_shows_form()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.login_end_user'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.login_end_user');
    }

    public function test_login_end_user_logged_in_redirects()
    {
        session(['end_user_portal_email' => 'some@example.com']);
        $response = $this->get(route('laporpoliwangi.end_user_portal.login_end_user'));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.my_ticket'));
    }

    public function test_login_end_user_submit_no_account()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'No',
            'last_name' => 'Account',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'noaccount@example.com',
            'customer_id' => $customerId,
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'noaccount@example.com',
            'password' => 'secret123',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_end_user_submit_wrong_password()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Wrong',
            'last_name' => 'Password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailId = \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'wrongpass@example.com',
            'customer_id' => $customerId,
        ]);
        \Modules\LaporPoliwangi\Models\EndUserPortalAccount::create([
            'customer_id' => $customerId,
            'email_id' => $emailId,
            'auth_type' => 'password',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'wrongpass@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_end_user_submit_not_verified()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Not',
            'last_name' => 'Verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailId = \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'notverified@example.com',
            'customer_id' => $customerId,
        ]);
        \Modules\LaporPoliwangi\Models\EndUserPortalAccount::create([
            'customer_id' => $customerId,
            'email_id' => $emailId,
            'auth_type' => 'password',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'notverified@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_register_end_user_displays_form()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.register'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.register_end_user');
    }

    public function test_register_end_user_submit_validation_fails()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            // empty payload
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_end_user_submit_success()
    {
        Event::fake();

        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => 'New Guy',
            'email' => 'newguy@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'redirect' => '/help'
        ]);

        $response->assertRedirect('/help/login?redirect=' . urlencode('/help'));
        
        $customer = Customer::where('first_name', 'New Guy')->first();
        $this->assertNotNull($customer);

        $this->assertDatabaseHas('end_user_portal_accounts', [
            'customer_id' => $customer->id,
            'auth_type' => 'password',
        ]);
    }

    public function test_register_end_user_logged_in_redirects()
    {
        session(['end_user_portal_email' => 'some@example.com']);
        $response = $this->get(route('laporpoliwangi.end_user_portal.register'));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.my_ticket'));
    }

    public function test_register_end_user_submit_existing_account_fails()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Existing',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailId = \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'existingaccount@example.com',
            'customer_id' => $customerId,
        ]);
        \Modules\LaporPoliwangi\Models\EndUserPortalAccount::create([
            'customer_id' => $customerId,
            'email_id' => $emailId,
            'auth_type' => 'password',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => 'Existing User',
            'email' => 'existingaccount@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'redirect' => '/help'
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_register_end_user_submit_existing_email_no_account()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Existing2',
            'last_name' => 'User2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'existingnoaccount@example.com',
            'customer_id' => $customerId,
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => 'Existing User 2',
            'email' => 'existingnoaccount@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'redirect' => '/help'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('end_user_portal_accounts', [
            'customer_id' => $customerId,
            'auth_type' => 'password',
        ]);
    }

    public function test_register_end_user_submit_mail_exception()
    {
        $originalMailer = app('mailer');
        app()->instance('mailer', new class {
            public function __call($method, $parameters) {
                throw new \Exception('Mail Server Down');
            }
        });

        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => 'Mail Guy',
            'email' => 'mailguy@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'redirect' => '/help'
        ]);

        app()->instance('mailer', $originalMailer);

        $response->assertRedirect();
        $this->assertDatabaseHas('end_user_portal_accounts', [
            'auth_type' => 'password',
        ]);
    }

    public function test_verify_email_invalid_token()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.verify', ['token' => 'invalid-token']));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.login_end_user'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_verify_email_success()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Verify',
            'last_name' => 'Me',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $emailId = \Illuminate\Support\Facades\DB::table('emails')->insertGetId([
            'email' => 'verifyme@example.com',
            'customer_id' => $customerId,
        ]);
        $account = \Modules\LaporPoliwangi\Models\EndUserPortalAccount::create([
            'customer_id' => $customerId,
            'email_id' => $emailId,
            'auth_type' => 'password',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'verification_token' => 'valid-token-123',
            'email_verified_at' => null,
        ]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.verify', ['token' => 'valid-token-123', 'redirect' => '/help']));
        $response->assertRedirect(url('/help'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('end_user_portal_email', 'verifyme@example.com');
        $response->assertSessionHas('end_user_portal_customer_id', $customerId);

        $account->refresh();
        $this->assertNull($account->verification_token);
        $this->assertNotNull($account->email_verified_at);
    }

    // ==========================================
    // PHASE 4: Ticket Management (myTickets, ticketDetail, replyTicket)
    // ==========================================

    public function test_my_tickets_unauthenticated_shows_track_ticket()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.track_ticket');
    }

    public function test_my_tickets_displays_tickets()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Test Subject',
            'number' => '12345',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = Conversation::find($conversationId);
        
        \Illuminate\Support\Facades\DB::table('threads')->insert([
            'conversation_id' => $conversation->id,
            'customer_id' => $customerId,
            'type' => \App\Thread::TYPE_CUSTOMER,
            'status' => \App\Thread::STATUS_ACTIVE,
            'state' => \App\Thread::STATE_PUBLISHED,
            'body' => 'First message body',
            'source_via' => \App\Thread::PERSON_CUSTOMER,
        ]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.my_ticket');
        $this->assertStringContainsString('Test Subject', $response->getContent());
    }

    public function test_ticket_detail_unauthenticated_redirects()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.ticket_detail', [$this->mailbox->id, 999]));
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_ticket_detail_displays_conversation()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Test Subject Detail',
            'number' => '12345',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = Conversation::find($conversationId);

        $response = $this->get(route('laporpoliwangi.end_user_portal.ticket_detail', [$this->mailbox->id, $conversation->id]));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.ticket_detail');
        $this->assertStringContainsString('Test Subject Detail', $response->getContent());
    }

    public function test_reply_ticket_unauthenticated_redirects()
    {
        $response = $this->post(route('laporpoliwangi.end_user_portal.ticket_reply', [$this->mailbox->id, 999]), [
            'message' => 'Test reply',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_reply_ticket_success()
    {
        Event::fake();
        
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Test Subject Reply',
            'number' => '12345',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = Conversation::find($conversationId);

        $response = $this->post(route('laporpoliwangi.end_user_portal.ticket_reply', [$this->mailbox->id, $conversation->id]), [
            'message' => 'This is a test reply',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'This is a test reply',
            'type' => \App\Thread::TYPE_CUSTOMER,
        ]);
    }

    public function test_reply_ticket_fails_on_closed_ticket()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Test Subject Reply Closed',
            'number' => '12345',
            'status' => Conversation::STATUS_CLOSED,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = Conversation::find($conversationId);

        $response = $this->post(route('laporpoliwangi.end_user_portal.ticket_reply', [$this->mailbox->id, $conversation->id]), [
            'message' => 'This is a test reply',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['message']);
    }

    // ==========================================
    // PHASE 5: SSO
    // ==========================================

    public function test_redirect_to_poliwangi_sso()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.sso.poliwangi'));
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_handle_poliwangi_sso_callback()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.sso.poliwangi.callback'));
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    // ==========================================
    // PHASE 6: Notification Channels Coverage
    // ==========================================

    public function test_submit_ticket_with_notification_channel()
    {
        Event::fake(); // Prevent actual emails/notifications if any

        \Modules\LaporPoliwangi\Models\NotificationChannel::create([
            'mailbox_id' => $this->mailbox->id,
            'type' => 'telegram',
            'is_active' => true,
            'name' => 'Test Telegram',
            'config' => []
        ]);

        \Modules\LaporPoliwangi\Models\NotificationChannel::create([
            'mailbox_id' => $this->mailbox->id,
            'type' => 'whatsapp',
            'is_active' => true,
            'name' => 'Test WhatsApp',
            'config' => []
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'email' => '',
            'name' => '',
            'subject' => 'Test Notification',
            'message' => 'This should trigger notification logic.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_submit_ticket_anonymous()
    {
        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'message' => 'Anonymous report message',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('conversations', [
            'subject' => 'New Ticket from End-User Portal',
        ]);
    }

    public function test_submit_ticket_logged_in_with_attachments_and_custom_fields()
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        rename($tempFile, $tempFile . '.jpg');
        $tempFile .= '.jpg';
        file_put_contents($tempFile, 'dummy content');
        $file = new \Illuminate\Http\UploadedFile($tempFile, 'test.jpg', 'image/jpeg', null, null, true);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Logged',
            'last_name' => 'In',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'loggedin@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'loggedin@example.com', 'end_user_portal_customer_id' => $customerId]);

        $setting = \Modules\LaporPoliwangi\Models\EndUserPortalSetting::firstOrCreate(
            ['mailbox_id' => $this->mailbox->id],
            ['subject_field' => true, 'consent_checkbox' => true]
        );

        try {
            $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
                'subject' => 'Test Subject',
                'message' => 'Test message',
                'consent' => 'on',
                'attachments' => [$file],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            dump($e->errors());
            throw $e;
        }

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_my_tickets_search_and_sort()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket', [
            'search' => 'Query',
            'sort' => 'updated_at',
            'direction' => 'asc'
        ]));

        $response->assertStatus(200);
    }

    public function test_reply_ticket_with_attachments()
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test');
        rename($tempFile2, $tempFile2 . '.pdf');
        $tempFile2 .= '.pdf';
        file_put_contents($tempFile2, 'dummy content');
        $file = new \Illuminate\Http\UploadedFile($tempFile2, 'document.pdf', 'application/pdf', null, null, true);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'usertest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'usertest@example.com']);

        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Test Subject Reply',
            'number' => '12345',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.ticket_reply', [$this->mailbox->id, $conversationId]), [
            'message' => 'Reply with attachment',
            'attachments' => [$file],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_register_end_user_validation_fails()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_end_user_success()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'name' => 'Valid Name',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_login_end_user_wrong_password()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Valid',
            'last_name' => 'Name',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'loginuser@example.com',
            'customer_id' => $customerId,
        ]);
        \Illuminate\Support\Facades\DB::table('end_user_portal_accounts')->insert([
            'customer_id' => $customerId,
            'auth_type' => 'password',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'loginuser@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_safe_redirect_method_returns_correct_urls()
    {
        $controller = app(\Modules\LaporPoliwangi\Http\Controllers\EndUserPortalController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('safeRedirect');
        $method->setAccessible(true);

        $defaultRoute = route('laporpoliwangi.end_user_portal.my_ticket');

        // Path 1: Not a string
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, [null]));
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, [123]));
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, [['array']]));

        // Path 2: HTTP/HTTPS (external)
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, ['http://example.com']));
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, ['https://example.com/help']));

        // Path 3: Doesn't start with /help
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, ['/admin/dashboard']));
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, ['/login']));
        $this->assertEquals($defaultRoute, $method->invokeArgs($controller, ['some-random-string']));

        // Path 4: Valid internal redirect
        $this->assertEquals('/help', $method->invokeArgs($controller, ['/help']));
        $this->assertEquals('/help/article/1', $method->invokeArgs($controller, ['/help/article/1']));
    }

    public function test_show_portal_without_mailboxes_returns_404()
    {
        $this->withExceptionHandling();
        // Remove all mailboxes to trigger the isEmpty() branch
        \Illuminate\Support\Facades\DB::table('mailboxes')->delete();
        $response = $this->get(route('laporpoliwangi.end_user_portal.submit_ticket', ['mailbox_id' => 999]));
        $response->assertStatus(404);
    }

    public function test_show_portal_with_null_mailbox_id_uses_first_mailbox()
    {
        $controller = app(\Modules\LaporPoliwangi\Http\Controllers\EndUserPortalController::class);
        $view = $controller->showPortal(null);
        
        $this->assertEquals('laporpoliwangi::end_user_portal.submit_ticket', $view->name());
        $this->assertEquals($this->mailbox->id, $view->getData()['mailbox']->id);
    }

    public function test_show_portal_without_allowed_custom_fields()
    {
        $setting = \Modules\LaporPoliwangi\Models\EndUserPortalSetting::firstOrCreate([
            'mailbox_id' => $this->mailbox->id
        ]);
        $setting->update(['custom_fields' => []]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.submit_ticket', ['mailbox_id' => $this->mailbox->id]));
        $response->assertStatus(200);
        $this->assertEmpty($response->original->getData()['customFields']);
    }

    public function test_my_tickets_empty_customer_ids()
    {
        session(['end_user_portal_email' => 'doesnotexist@example.com']);
        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.my_ticket');
        $this->assertEmpty($response->original->getData()['tickets']);
    }

    public function test_my_tickets_branches()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'branchtest@example.com',
            'customer_id' => $customerId,
        ]);
        session(['end_user_portal_email' => 'branchtest@example.com']);

        // Closed conversation with no last_reply_at and long body
        $conversationId = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Closed Ticket',
            'number' => '54321',
            'status' => Conversation::STATUS_CLOSED,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'last_reply_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $conversationId2 = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'No Activity Ticket',
            'number' => '54322',
            'status' => Conversation::STATUS_CLOSED,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'last_reply_at' => null,
            'created_at' => now(),
            'updated_at' => null,
        ]);

        $conversationId3 = \Illuminate\Support\Facades\DB::table('conversations')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customerId,
            'subject' => 'Ticket with Reply',
            'number' => '54323',
            'status' => Conversation::STATUS_CLOSED,
            'state' => Conversation::STATE_PUBLISHED,
            'type' => Conversation::TYPE_EMAIL,
            'last_reply_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $longBody = str_repeat('A long message body ', 10); // > 120 chars
        \Illuminate\Support\Facades\DB::table('threads')->insert([
            'conversation_id' => $conversationId,
            'customer_id' => $customerId,
            'type' => \App\Thread::TYPE_CUSTOMER,
            'status' => \App\Thread::STATUS_ACTIVE,
            'state' => \App\Thread::STATE_PUBLISHED,
            'body' => $longBody,
            'source_via' => \App\Thread::PERSON_CUSTOMER,
        ]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertStatus(200);
        $tickets = $response->original->getData()['tickets'];
        $this->assertCount(3, $tickets);
        $this->assertEquals('Closed', $tickets[0]['status']);
        $this->assertStringEndsWith('...', $tickets[0]['preview']);
    }

    public function test_submit_ticket_unregistered_email_fails()
    {
        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'name' => 'Test Name',
            'email' => 'unregistered@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_submit_ticket_anonymous_with_registered_email()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Registered',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'registered@example.com',
            'customer_id' => $customerId,
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'name' => 'Test Name',
            'email' => 'registered@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test Message'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('conversations', [
            'subject' => 'Test Subject',
            'customer_id' => $customerId,
        ]);
    }

    public function test_submit_ticket_custom_fields_branches()
    {
        $setting = EndUserPortalSetting::where('mailbox_id', $this->mailbox->id)->first();
        
        $cf1 = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Text Field',
            'type' => 'text',
        ]);
        
        $cf2 = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Array Field',
            'type' => 'checkbox',
        ]);

        $setting->update([
            'custom_fields' => json_encode([$cf1->id, $cf2->id]),
            'subject_field' => false,
            'consent_checkbox' => true
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'name' => '',
            'email' => '',
            'subject' => 'Test Custom Fields',
            'message' => 'Test Message',
            'consent' => 'on',
            'custom_fields' => [
                $cf1->id => '', 
                $cf2->id => ['Option 1', 'Option 2'], 
                9999 => 'Invalid Field', 
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $conversation = Conversation::where('subject', 'Test Custom Fields')->first();
        $this->assertNotNull($conversation);
        
        // CF2 should be saved as JSON array
        $val2 = \Modules\LaporPoliwangi\Models\CustomFieldValue::where('conversation_id', $conversation->id)
            ->where('custom_field_id', $cf2->id)
            ->first();
        $this->assertEquals(json_encode(['Option 1', 'Option 2']), $val2->value);
        
        // CF1 (empty) and 9999 (invalid) should not be saved
        $val1 = \Modules\LaporPoliwangi\Models\CustomFieldValue::where('conversation_id', $conversation->id)
            ->where('custom_field_id', $cf1->id)
            ->first();
        $this->assertNull($val1);
    }

    public function test_show_portal_logged_in()
    {
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'User',
            'last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        session([
            'end_user_portal_email' => 'testloggedin@example.com',
            'end_user_portal_customer_id' => $customerId
        ]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.submit_ticket', ['mailbox_id' => $this->mailbox->id]));
        $response->assertStatus(200);
        $response->assertViewHas('loggedCustomer');
        $this->assertEquals($customerId, $response->original->getData()['loggedCustomer']->id);
    }
    // ==========================================
    // PHASE 6: Private Methods (Reflection)
    // ==========================================

    public function test_normalizeNotificationBody_all_paths()
    {
        $controller = app(\Modules\LaporPoliwangi\Http\Controllers\EndUserPortalController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('normalizeNotificationBody');
        $method->setAccessible(true);
        
        // Empty or null
        $this->assertEquals('-', $method->invoke($controller, null));
        $this->assertEquals('-', $method->invoke($controller, ''));
        $this->assertEquals('-', $method->invoke($controller, '   '));
        
        // Long string
        $longStr = str_repeat('a', 1500);
        $result = $method->invoke($controller, $longStr);
        $this->assertEquals(str_repeat('a', 1000) . '...', $result);
        
        // HTML strip
        $this->assertEquals('test', $method->invoke($controller, '<b>test</b>'));
    }

    public function test_resolveConversationEmail_all_paths()
    {
        $controller = app(\Modules\LaporPoliwangi\Http\Controllers\EndUserPortalController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('resolveConversationEmail');
        $method->setAccessible(true);

        $conversation = new Conversation();
        $thread = new \App\Thread();

        // 1. When conversations has customer_email and it's not empty
        $conversation->customer_email = 'customer_email@example.com';
        $result = $method->invoke($controller, $conversation, $thread);
        $this->assertEquals('customer_email@example.com', $result);

        // 2. When conversations customer_email is empty/null, but thread->from is not empty
        $conversation->customer_email = '';
        $thread->from = 'thread_from@example.com';
        $result = $method->invoke($controller, $conversation, $thread);
        $this->assertEquals('thread_from@example.com', $result);
    }

    // ==========================================
    // PHASE 7: Ticket Tracking System
    // ==========================================

    public function test_track_ticket_submit_validation_fails()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), []);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['ticket_number', 'email']);
    }

    public function test_track_ticket_submit_with_tracking_code_invalid()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'tracking_code' => 'INVALIDCODE'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['tracking_code']);
    }

    public function test_track_ticket_submit_with_tracking_code_valid_but_no_conversation()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'validcode@anonim.local',
            'customer_id' => $customerId,
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'tracking_code' => 'validcode'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['tracking_code']);
    }

    public function test_track_ticket_submit_with_tracking_code_success()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'validcode@anonim.local',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'tracking_code' => 'validcode'
        ]);
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $this->assertEquals($number, session('tracking_authenticated_ticket'));
    }

    public function test_track_ticket_submit_with_email_and_number_not_found()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'ticket_number' => 999999,
            'email' => 'notfound@example.com'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['ticket_number']);
    }

    public function test_track_ticket_submit_with_email_and_number_mismatch()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'correct@example.com',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'ticket_number' => $number,
            'email' => 'wrong@example.com'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_track_ticket_submit_with_email_and_number_success()
    {
        $this->withExceptionHandling();
        config([
            'mail.driver' => 'array',
            'mail.from' => ['address' => 'helpdesk@lapor.poliwangi', 'name' => 'Lapor Poliwangi Helpdesk']
        ]);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'correct@example.com',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'ticket_number' => $number,
            'email' => 'correct@example.com'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_track_ticket_submit_with_customer_email_direct_match()
    {
        $this->withExceptionHandling();
        config([
            'mail.driver' => 'array',
            'mail.from' => ['address' => 'helpdesk@lapor.poliwangi', 'name' => 'Lapor Poliwangi Helpdesk']
        ]);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Direct',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->customer_email = 'direct@example.com';
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Direct Match Tracking';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'ticket_number' => $number,
            'email' => 'direct@example.com'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_track_ticket_submit_mail_sending_fails()
    {
        $this->withExceptionHandling();
        
        config(['mail.driver' => 'invalid_driver_to_force_failure']);

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Fail',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'correct@example.com',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Mail Failure Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track.submit'), [
            'ticket_number' => $number,
            'email' => 'correct@example.com'
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_verify_tracking_token_invalid()
    {
        $this->withExceptionHandling();
        $response = $this->get(route('laporpoliwangi.end_user_portal.track.verify', ['token' => 'INVALIDTOKEN']));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertSessionHasErrors(['message']);
    }

    public function test_verify_tracking_token_success()
    {
        $this->withExceptionHandling();
        $token = 'VALIDTOKEN123';
        \Illuminate\Support\Facades\Cache::put('track_token_' . $token, 123456, 60);

        $response = $this->get(route('laporpoliwangi.end_user_portal.track.verify', ['token' => $token]));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.track_detail', 123456));
        $this->assertEquals(123456, session('tracking_authenticated_ticket'));
    }

    public function test_track_ticket_detail_unauthenticated()
    {
        $this->withExceptionHandling();
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = 1;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;
        $response = $this->get(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertSessionHasErrors(['message']);
    }

    public function test_track_ticket_detail_authenticated_session()
    {
        $this->withExceptionHandling();
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = 1;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['tracking_authenticated_ticket' => $number]);

        $response = $this->get(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.track_ticket_detail');
    }

    public function test_track_ticket_detail_authenticated_portal_email_direct()
    {
        $this->withExceptionHandling();
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = 1;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->customer_email = 'portal@example.com';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['end_user_portal_email' => 'portal@example.com']);

        $response = $this->get(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.track_ticket_detail');
    }

    public function test_track_ticket_detail_authenticated_portal_email_customer_relation()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'portalrel@example.com',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['end_user_portal_email' => 'portalrel@example.com']);

        $response = $this->get(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::end_user_portal.track_ticket_detail');
    }

    public function test_track_ticket_reply_unauthenticated()
    {
        $this->withExceptionHandling();
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = 1;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        $response = $this->post(route('laporpoliwangi.end_user_portal.track_reply', $number), [
            'message' => 'Hello reply'
        ]);
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.my_ticket'));
    }

    public function test_track_ticket_reply_closed_ticket()
    {
        $this->withExceptionHandling();
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = 1;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->status = Conversation::STATUS_CLOSED;
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['tracking_authenticated_ticket' => $number]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.track_reply', $number), [
            'message' => 'Hello reply'
        ]);
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertSessionHasErrors(['message']);
    }

    public function test_track_ticket_reply_success()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->customer_email = 'customer@example.com';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['tracking_authenticated_ticket' => $number]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.track_reply', $number), [
            'message' => 'Test reply message'
        ]);
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Test reply message'
        ]);
    }

    public function test_track_ticket_reply_success_with_attachments()
    {
        $this->withExceptionHandling();
        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Track',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Tracking Detail Subject';
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['tracking_authenticated_ticket' => $number]);

        \Illuminate\Support\Facades\Storage::fake('local');
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        rename($tempFile, $tempFile . '.pdf');
        $tempFile .= '.pdf';
        file_put_contents($tempFile, 'dummy content');
        $file = new \Illuminate\Http\UploadedFile($tempFile, 'document.pdf', 'application/pdf', null, null, true);

        $response = $this->post(route('laporpoliwangi.end_user_portal.track_reply', $number), [
            'message' => 'Reply with attachment',
            'attachments' => [$file]
        ]);
        $response->assertRedirect(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertSessionHas('success');
    }

    public function test_track_ticket_detail_and_reply_via_customer_email_fallback()
    {
        $this->withExceptionHandling();

        $customerId = \Illuminate\Support\Facades\DB::table('customers')->insertGetId([
            'first_name' => 'Fallback',
            'last_name' => 'User',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        \Illuminate\Support\Facades\DB::table('emails')->insert([
            'email' => 'fallback-customer@example.com',
            'customer_id' => $customerId,
        ]);

        $conversation = new Conversation();
        $conversation->mailbox_id = $this->mailbox->id;
        $conversation->customer_id = $customerId;
        $conversation->customer_email = null;
        $conversation->state = Conversation::STATE_PUBLISHED;
        $conversation->subject = 'Fallback Test Subject';
        $conversation->status = Conversation::STATUS_ACTIVE;
        $conversation->save();
        $conversation->refresh();
        $number = $conversation->number;

        session(['end_user_portal_email' => 'fallback-customer@example.com']);

        $response = $this->get(route('laporpoliwangi.end_user_portal.track_detail', $number));
        $response->assertStatus(200);

        $replyResponse = $this->post(route('laporpoliwangi.end_user_portal.track_reply', $number), [
            'message' => 'This is a reply using customer email fallback.',
        ]);
        $replyResponse->assertRedirect();
    }
}
