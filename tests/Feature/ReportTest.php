<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use App\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $mailbox;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        \Session::start();

        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->user = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();

        $this->mailbox->users()->sync([
            $this->admin->id
        ]);

        $this->conversation = factory(Conversation::class)->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        if (!Schema::hasColumn('conversations', 'closed_at')) {
            Schema::table('conversations', function ($table) {
                $table->timestamp('closed_at')->nullable();
            });
        }

        $this->actingAs($this->admin);
    }

    public function test_time_tracking_report_loads_successfully()
    {
        $response = $this->get(route('laporpoliwangi.reports.time_tracking'));
        $response->assertStatus(200);
        $response->assertViewIs('laporpoliwangi::reports.time_tracking');
    }

    public function test_time_tracking_report_with_no_mailboxes()
    {
        $userNoMailbox = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $response = $this->actingAs($userNoMailbox)->get(route('laporpoliwangi.reports.time_tracking'));
        $response->assertStatus(200);
    }

    public function test_time_tracking_report_unauthorized()
    {
        $this->withExceptionHandling();
        $this->actingAs($this->user);

        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(403);
    }

    public function test_time_tracking_report_with_mailbox_mismatch_falls_back()
    {
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
            'mailbox' => 99999
        ]));

        $response->assertStatus(200);
        // Should fall back to the admin's accessible mailbox
        $response->assertViewHas('selectedMailboxId', $this->mailbox->id);
    }

    public function test_time_tracking_report_date_ranges()
    {
        $ranges = [
            'today',
            'yesterday',
            'last_7_days',
            'last_week',
            'month',
            'last_month',
            'last_12_months',
            'year',
            'custom'
        ];

        foreach ($ranges as $range) {
            $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
                'mailbox' => $this->mailbox->id,
                'range' => $range,
                'date_from' => '2026-01-01',
                'date_to' => '2026-12-31'
            ]));
            $response->assertStatus(200);
        }
    }

    public function test_time_tracking_report_modes_and_types()
    {
        // 1. Off mode
        \App\Option::$cache = [];
        \App\Option::set('time_tracking_mode', 'off');
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);

        // 2. Reply mode
        \App\Option::$cache = [];
        \App\Option::set('time_tracking_mode', 'reply');
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);

        // 3. Note mode
        \App\Option::$cache = [];
        \App\Option::set('time_tracking_mode', 'note');
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);

        // 4. Manual types override
        \App\Option::$cache = [];
        \App\Option::set('time_tracking_mode', 'assigned');
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id, 'type' => 'reply']));
        $response->assertStatus(200);

        \App\Option::$cache = [];
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id, 'type' => 'note']));
        $response->assertStatus(200);
    }

    public function test_time_tracking_report_custom_fields_filtering()
    {
        // Create custom field
        $fieldId = DB::table('custom_fields')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'type_field' => 'dropdown',
            'nama_field' => 'Department',
            'options' => json_encode(['Sales', 'Support']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('custom_field_values')->insert([
            'custom_field_id' => $fieldId,
            'conversation_id' => $this->conversation->id,
            'value' => 'Support',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
            'mailbox' => $this->mailbox->id,
            'custom_fields' => [
                $fieldId => 'Support',
                999 => '' // Empty string to cover continue
            ]
        ]));

        $response->assertStatus(200);
    }

    public function test_time_tracking_report_time_logs_and_durations()
    {
        // 1. Seconds < 60
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 45, // 45 seconds
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seconds >= 60 && < 3600
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 120, // 2 minutes
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seconds >= 3600 (plural hour, plural minute)
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 7320, // 2 hours, 2 minutes
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Seconds exactly 3660 (1 hour, 1 minute)
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 3660, // 1 hour, 1 minute
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Invalid / negative seconds to test continue check
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => -10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Thread update to count updates
        $thread = new Thread();
        $thread->conversation_id = $this->conversation->id;
        $thread->created_by_user_id = $this->admin->id;
        $thread->type = Thread::TYPE_MESSAGE;
        $thread->save();

        // Customer for customer stats
        $customerId = DB::table('customers')->insertGetId([
            'first_name' => 'Report',
            'last_name' => 'Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('emails')->insert([
            'email' => 'report-customer@example.com',
            'customer_id' => $customerId,
        ]);

        $this->conversation->customer_id = $customerId;
        $this->conversation->save();

        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);
    }

    public function test_time_tracking_report_csv_export()
    {
        // Insert a log
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 3600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $this->conversation->id;
        $thread->created_by_user_id = $this->admin->id;
        $thread->type = Thread::TYPE_MESSAGE;
        $thread->save();

        // Customer
        $customerId = DB::table('customers')->insertGetId([
            'first_name' => 'CSV',
            'last_name' => 'Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('emails')->insert([
            'email' => 'csv-customer@example.com',
            'customer_id' => $customerId,
        ]);

        $this->conversation->customer_id = $customerId;
        $this->conversation->save();

        $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
            'mailbox' => $this->mailbox->id,
            'export' => 'csv'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="time-tracking-report.csv"');
        $this->assertStringContainsString('Time Spent Hours', $response->getContent());
        $this->assertStringContainsString('csv-customer@example.com', $response->getContent());
    }

    public function test_time_tracking_report_misc_coverage()
    {
        // 0. Insert a log with 45 seconds to cover formatTimeSpent < 60 seconds branch
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert a second log on a different day (yesterday) to cover the usort comparison callback (line 494)
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 60,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // Create a second user and assign them to the mailbox with 0 updates/logs (covers line 385)
        $userNoLogs = factory(User::class)->create();
        $this->mailbox->users()->attach($userNoLogs->id);

        // Create another conversation with a customer, but no logs (covers line 439)
        $customerIdNoLogs = DB::table('customers')->insertGetId([
            'first_name' => 'NoLogs',
            'last_name' => 'Customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('emails')->insert([
            'email' => 'nologs-customer@example.com',
            'customer_id' => $customerIdNoLogs,
        ]);
        factory(Conversation::class)->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATE_PUBLISHED,
            'customer_id' => $customerIdNoLogs,
        ]);

        // 1. Non-array selected custom fields (covers line 150)
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
            'mailbox' => $this->mailbox->id,
            'custom_fields' => 'not-an-array'
        ]));
        $response->assertStatus(200);

        // 2. Custom field with null/empty options (covers line 129)
        $fieldId = DB::table('custom_fields')->insertGetId([
            'mailbox_id' => $this->mailbox->id,
            'type_field' => 'text',
            'nama_field' => 'No Options Field',
            'options' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);

        // 3. Exactly 3600 seconds (singular hour, zero minutes) to cover formatTimeSpent branch
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 3600, // 1 hour exactly
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $response = $this->get(route('laporpoliwangi.reports.time_tracking', ['mailbox' => $this->mailbox->id]));
        $response->assertStatus(200);

        // 4. Conversation without number and subject fallbacks in CSV
        $convNoNumber = factory(Conversation::class)->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATE_PUBLISHED,
            'subject' => '',
            'preview' => 'Conversation Preview Text',
        ]);
        
        // Ensure its number is also set to empty/null if possible
        DB::table('conversations')->where('id', $convNoNumber->id)->update([
            'number' => null,
        ]);

        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $convNoNumber->id,
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->admin->id,
            'seconds' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Thread
        $thread = new Thread();
        $thread->conversation_id = $convNoNumber->id;
        $thread->created_by_user_id = $this->admin->id;
        $thread->type = Thread::TYPE_MESSAGE;
        $thread->save();

        $response = $this->get(route('laporpoliwangi.reports.time_tracking', [
            'mailbox' => $this->mailbox->id,
            'export' => 'csv'
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('Conversation Preview Text', $response->getContent());
    }
}
