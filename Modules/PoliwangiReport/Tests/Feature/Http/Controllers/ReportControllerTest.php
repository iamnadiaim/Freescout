<?php

namespace Modules\PoliwangiReport\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use App\Thread;
use App\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\PoliwangiTimeTracking\Models\TimeTrackingLog;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected static $reportMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$reportMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiTimeTracking/Database/Migrations']);
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiCustomField/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$reportMigrated = true;
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

        $this->app->register(\Modules\PoliwangiReport\Providers\PoliwangiReportServiceProvider::class);
        $this->app->register(\Modules\PoliwangiCustomField\Providers\PoliwangiCustomFieldServiceProvider::class);
        $this->app->register(\Modules\PoliwangiTimeTracking\Providers\PoliwangiTimeTrackingServiceProvider::class);
        
        \App\Option::$cache = [];

        // Global decorator for Module::isActive
        $originalModules = app('modules');
        $this->app->instance('modules', new class($originalModules) {
            private $original;
            public function __construct($original) { $this->original = $original; }
            public function isActive($name) {
                if (strtolower($name) === 'poliwangicustomfield') return true;
                return $this->original->isActive($name);
            }
            public function __call($method, $args) {
                return $this->original->$method(...$args);
            }
        });
    }

    public function test_time_tracking_requires_authentication()
    {
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');
    }

    public function test_time_tracking_aborts_if_user_has_no_mailboxes()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        
        $this->actingAs($user);
        $this->withExceptionHandling();

        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');
        
        $response->assertStatus(403);
    }

    public function test_time_tracking_accessible_for_admin()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        
        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');
        
        $response->assertStatus(200);
        $response->assertViewIs('poliwangireport::reports.time_tracking');
    }

    public function test_time_tracking_accessible_for_user_with_permission()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        $mailbox = factory(Mailbox::class)->create();
        
        $user->mailboxes()->attach($mailbox->id, ['access' => json_encode([\App\Mailbox::ACCESS_PERM_EDIT])]);
        
        $this->actingAs($user);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');
        
        $response->assertStatus(200);
    }

    public function test_time_tracking_filters_invalid_mailbox()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox1 = factory(Mailbox::class)->create();
        $mailbox2 = factory(Mailbox::class)->create(); // valid tapi tidak kita pilih
        
        $this->actingAs($admin);
        
        // Pilih mailbox 999 yang tidak exist
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=999');
        
        $response->assertStatus(200);
        // Controller seharusnya mereset ke mailbox1 atau mailbox default jika invalid
        $response->assertViewHas('selectedMailboxId');
    }

    public function test_date_range_filters()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        $this->actingAs($admin);

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
            $url = \Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?range=' . $range;
            if ($range === 'custom') {
                $url .= '&date_from=2023-01-01&date_to=2023-12-31';
            }
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertViewHas('range', $range);
        }
    }

    public function test_time_tracking_with_data()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        $admin->mailboxes()->attach($mailbox->id, ['access' => json_encode([\App\Mailbox::ACCESS_PERM_EDIT])]);
        $customer = factory(Customer::class)->create();
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $admin->id,
            'type' => Thread::TYPE_MESSAGE
        ]);

        // Insert TimeTrackingLog
        // Asumsi struktur tabel timetrackinglogs: thread_id, user_id, time_spent (dalam detik)
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $conversation->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id,
            'seconds' => 3600, // 1 hour
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Empty conversation for fallback coverage
        $emptyCustomer = factory(Customer::class)->create();
        factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $emptyCustomer->id,
        ]);

        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id);
        $response->assertStatus(200);
        $response->assertViewHas('summary');
    }

    public function test_time_tracking_csv_export()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        $admin->mailboxes()->attach($mailbox->id, ['access' => json_encode([\App\Mailbox::ACCESS_PERM_EDIT])]);
        
        $customer = factory(Customer::class)->create(['first_name' => 'CSV', 'last_name' => 'Tester']);
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $admin->id,
            'type' => Thread::TYPE_MESSAGE
        ]);
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $conversation->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id,
            'seconds' => 3600,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        
        // Log for a different day to cover usort callback in daily stats
        DB::table('time_tracking_logs')->insert([
            'conversation_id' => $conversation->id,
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id,
            'seconds' => 1800,
            'created_at' => Carbon::yesterday(),
            'updated_at' => Carbon::yesterday(),
        ]);

        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?export=csv&mailbox=' . $mailbox->id);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="time-tracking-report.csv"');
        
        // Output the streamed content to trigger the callback
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        
        $this->assertStringContainsString('"Time Spent Hours"', $content);
        $this->assertStringContainsString('1', $content);
    }

    public function test_format_time_spent_private_method()
    {
        $controller = new \Modules\PoliwangiReport\Http\Controllers\ReportController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('formatTimeSpent');
        $method->setAccessible(true);

        $this->assertEquals('-', $method->invoke($controller, 0));
        $this->assertEquals('30 seconds', $method->invoke($controller, 0.5));
        $this->assertEquals('1 minute', $method->invoke($controller, 1));
        $this->assertEquals('1 hour', $method->invoke($controller, 60));
        $this->assertEquals('1 hour 30 minutes', $method->invoke($controller, 90));
        $this->assertEquals('2 hours', $method->invoke($controller, 120));
        $this->assertEquals('2 hours 5 minutes', $method->invoke($controller, 125));
    }

    public function test_custom_field_filters()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = factory(Mailbox::class)->create();
        $admin->mailboxes()->attach($mailbox->id, ['access' => json_encode([\App\Mailbox::ACCESS_PERM_EDIT])]);

        // Bikin custom field dan opsi jika tabelnya ada
        if (\Illuminate\Support\Facades\Schema::hasTable('custom_fields')) {
            $fieldId = DB::table('custom_fields')->insertGetId([
                'mailbox_id' => $mailbox->id,
                'nama_field' => 'Kategori',
                'type_field' => 'dropdown',
                'options' => json_encode([['value' => 'A', 'label' => 'A']]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $conversation = factory(Conversation::class)->create([
                'mailbox_id' => $mailbox->id,
            ]);

            DB::table('custom_field_values')->insert([
                'conversation_id' => $conversation->id,
                'custom_field_id' => $fieldId,
                'value' => 'A'
            ]);
        }

        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&custom_fields[Kategori]=A');
        
        $response->assertStatus(200);
        $response->assertViewHas('customFields');
    }

    public function test_all_date_ranges()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test Mailbox', 'email' => 'test@lapor.poliwangi']);

        $ranges = ['today', 'yesterday', 'last_7_days', 'last_week', 'month', 'last_month', 'last_12_months', 'year', 'custom'];

        foreach ($ranges as $range) {
            $response = $this->actingAs($admin)
                ->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&range=' . $range);
            $response->assertStatus(200);
        }
    }

    public function test_time_tracking_modes()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test Mailbox', 'email' => 'test@lapor.poliwangi']);

        \App\Option::set('time_tracking_mode', 'off');
        \App\Option::$cache = [];
        $response = $this->actingAs($admin)
            ->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id);
        $response->assertStatus(200);

        \App\Option::set('time_tracking_mode', 'reply');
        \App\Option::$cache = [];
        $response = $this->actingAs($admin)
            ->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id);
        $response->assertStatus(200);

        \App\Option::set('time_tracking_mode', 'note');
        \App\Option::$cache = [];
        $response = $this->actingAs($admin)
            ->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id);
        $response->assertStatus(200);
    }

    public function test_custom_field_empty_string_and_null()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test']);
        
        $this->actingAs($admin);
        
        // Pass empty string
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&custom_fields[1]=');
        $response->assertStatus(200);

        // Pass invalid non-array custom_fields
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&custom_fields=invalid');
        $response->assertStatus(200);
    }

    public function test_report_type_filter()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test']);
        
        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&type=reply');
        $response->assertStatus(200);

        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id . '&type=note');
        $response->assertStatus(200);
    }

    public function test_invalid_custom_field_options()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test']);
        
        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiCustomField');
        if ($module) {
            $module->enable();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('custom_fields')) {
            DB::table('custom_fields')->insert([
                'mailbox_id' => $mailbox->id,
                'nama_field' => 'Invalid Options',
                'type_field' => 'dropdown',
                'options' => '"not an array"',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->actingAs($admin);
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report?mailbox=' . $mailbox->id);
        $response->assertStatus(200);
    }

    public function test_report_without_mailbox()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');
        $response->assertStatus(200);
    }
}
