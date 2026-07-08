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

    public function test_submit_ticket_success_guest()
    {
        Event::fake(); // Prevent actual emails/notifications

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'email' => 'newuser@example.com',
            'name' => 'John Doe',
            'subject' => 'Help with login',
            'message' => 'I cannot login to my account.'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if ticket was created
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Help with login',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('customers', [
            'first_name' => 'John Doe',
            'last_name' => '',
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
            ['custom_fields' => json_encode([$field->id])]
        );
        $setting->update(['custom_fields' => json_encode([$field->id])]);


        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'email' => 'student@example.com',
            'name' => 'Jane Doe',
            'subject' => 'Request ID',
            'message' => 'Please provide me an ID.',
            'custom_fields' => [
                $field->id => '123456789'
            ]
        ]);

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
        ]);

        $response = $this->post(route('laporpoliwangi.end_user_portal.login.submit'), [
            'email' => 'enduser@example.com',
            'password' => 'password123',
            'redirect' => '/help'
        ]);

        $response->assertRedirect('/help');
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

    // ==========================================
    // PHASE 4: Ticket Management (myTickets, ticketDetail, replyTicket)
    // ==========================================

    public function test_my_tickets_unauthenticated_redirects_to_login()
    {
        $response = $this->get(route('laporpoliwangi.end_user_portal.my_ticket'));
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
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
            'email' => 'notifuser@example.com',
            'name' => 'Notif User',
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
        $file = new \Illuminate\Http\UploadedFile($tempFile, 'test.jpg', 'image/jpeg', null, true);

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

        $response = $this->post(route('laporpoliwangi.end_user_portal.submit', ['mailbox_id' => $this->mailbox->id]), [
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'consent' => 'on',
            'attachments' => [$file],
        ]);

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
        $file = new \Illuminate\Http\UploadedFile($tempFile2, 'document.pdf', 'application/pdf', null, true);

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
            'first_name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'email', 'password']);
    }

    public function test_register_end_user_success()
    {
        $this->withExceptionHandling();
        $response = $this->post(route('laporpoliwangi.end_user_portal.register.submit'), [
            'first_name' => 'Valid',
            'last_name' => 'Name',
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
}
