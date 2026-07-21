<?php

namespace Modules\PoliwangiSatisfaction\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Conversation;
use App\Thread;
use App\Email;
use App\Customer;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRating;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SatisfactionRatingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected static $satisfactionMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$satisfactionMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiSatisfaction/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$satisfactionMigrated = true;
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

        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiSatisfaction');
        if ($module) {
            $module->enable();
        }
        $portalModule = \Nwidart\Modules\Facades\Module::find('PoliwangiPortal');
        if ($portalModule) {
            $portalModule->enable();
        }
        app()->register(\Modules\PoliwangiSatisfaction\Providers\PoliwangiSatisfactionServiceProvider::class);
        app()->register(\Modules\PoliwangiPortal\Providers\PoliwangiPortalServiceProvider::class);
    }

    public function test_authorize_settings_unauthorized()
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($user);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/settings', []);
    }

    public function test_authorize_settings_authorized_with_permission()
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasManageMailboxPermission', 'can'])
            ->getMock();
            
        $user->method('hasManageMailboxPermission')->willReturn(true);
        $user->method('can')->willReturn(true);
        $user->role = User::ROLE_USER;
        $user->id = 1;

        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($user);
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/settings', []);
    }

    public function test_index_settings()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings');
        $response->assertStatus(200);
    }

    public function test_update_settings()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($admin);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/settings', [
            'enabled' => 'on',
            'add_ratings_mode' => 'all',
            'placement' => 'above',
            'saving_mode' => 'immediate',
            'ratings_text' => 'Rate this',
        ]);
        
        $response->assertStatus(302);
        
        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
        $this->assertTrue($setting->enabled);
        $this->assertEquals('all', $setting->add_ratings_mode);
        $this->assertEquals('above', $setting->placement);
        $this->assertEquals('immediate', $setting->saving_mode);
        $this->assertEquals('Rate this', $setting->ratings_text);
    }

    public function test_update_translate()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($admin);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/translate', [
            'page_title' => 'Title',
            'header' => 'Header',
            'great_text' => 'Great',
            'okay_text' => 'Okay',
            'not_good_text' => 'Bad',
            'comment_box_text' => 'Comment',
            'comment_placeholder' => 'Placeholder',
            'send_button_text' => 'Send',
            'send_confirmation_text' => 'Thanks',
        ]);
        
        $response->assertStatus(302);
        
        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
        $this->assertEquals('Title', $setting->page_title);
        $this->assertEquals('Header', $setting->header);
        $this->assertEquals('Great', $setting->great_text);
        $this->assertEquals('Okay', $setting->okay_text);
        $this->assertEquals('Bad', $setting->not_good_text);
        $this->assertEquals('Comment', $setting->comment_box_text);
        $this->assertEquals('Placeholder', $setting->comment_placeholder);
        $this->assertEquals('Send', $setting->send_button_text);
        $this->assertEquals('Thanks', $setting->send_confirmation_text);
    }

    public function test_reset_settings_defaults()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => false,
            'placement' => 'above',
        ]));

        $this->actingAs($admin);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/settings/reset');
        $response->assertStatus(302);
        
        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
        $this->assertFalse($setting->enabled);
        $this->assertEquals('above', $setting->placement); // default is above
    }

    public function test_reset_translate_defaults()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'page_title' => 'Custom Title',
        ]));

        $this->actingAs($admin);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/translate/reset');
        $response->assertStatus(302);
        
        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
        $this->assertEquals('Satisfaction Ratings', $setting->page_title); // default
    }

    public function test_report()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        $this->actingAs($admin);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/satisfaction-ratings/report');
        $response->assertStatus(200);
    }
    
    public function test_rate_from_email_success()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED
        ]);
        
        $response = $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/' . $conversation->id . '/' . $thread->id . '/great?email=customer@test.com');
        $response->assertStatus(200);
        $response->assertViewIs('poliwangisatisfaction::satisfaction_ratings.thank_you');
        
        $rating = SatisfactionRating::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($rating);
        $this->assertEquals('great', $rating->rating);
    }

    public function test_submit_rating_from_portal()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED
        ]);
        
        $response = $this->withSession(['end_user_portal_email' => 'customer@test.com'])
            ->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
                'rating' => 'okay',
                'comment' => 'It was fine',
                'thread_id' => $thread->id,
                'saving_mode' => 'immediate',
            ]);
            
        $response->assertStatus(302);
        
        $rating = SatisfactionRating::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($rating);
        $this->assertEquals('okay', $rating->rating);
        $this->assertEquals('It was fine', $rating->comment);
    }
    
    public function test_submit_rating_from_portal_no_session()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED
        ]);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
            'rating' => 'okay',
            'comment' => 'It was fine',
            'thread_id' => $thread->id,
            'saving_mode' => 'immediate',
        ]);
            
        $response->assertStatus(302);
        // Will redirect to login
    }
    
    public function test_submit_rating_from_portal_disabled()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => false,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $thread = factory(Thread::class)->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED
        ]);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
            'rating' => 'okay',
            'comment' => 'It was fine',
            'thread_id' => $thread->id,
            'saving_mode' => 'immediate',
        ]);
            
        $response->assertStatus(302);
        // Redirects with error
    }

    public function test_submit_rating_invalid_conversation()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $response = $this->withSession(['end_user_portal_email' => 'customer@test.com'])
            ->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/999999/satisfaction-rating', [
                'rating' => 'okay',
            ]);
            
        $response->assertStatus(302);
    }

    public function test_submit_rating_wrong_customer()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        $customer2 = factory(Customer::class)->create();
        Email::create('other@test.com', $customer2->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $response = $this->withSession(['end_user_portal_email' => 'wrong@test.com'])
            ->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
                'rating' => 'okay',
            ]);
            
        $response->assertStatus(302);
    }

    public function test_submit_rating_invalid_thread()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $response = $this->withSession(['end_user_portal_email' => 'customer@test.com'])
            ->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
                'rating' => 'okay',
                'thread_id' => 999999, // Invalid thread
            ]);
            
        $response->assertStatus(302);
    }

    public function test_submit_rating_no_thread()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);
        
        $response = $this->withSession(['end_user_portal_email' => 'customer@test.com'])
            ->post(\Helper::getSubdirectory() . 'lapor-poliwangi/help/' . $mailbox->id . '/ticket/' . $conversation->id . '/satisfaction-rating', [
                'rating' => 'okay',
                // No thread_id provided
            ]);
            
        $response->assertStatus(302);
        
        $rating = SatisfactionRating::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($rating);
        $this->assertNull($rating->thread_id);
    }

    public function test_rate_from_email_disabled()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => false,
        ]));
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/1/1/great?email=customer@test.com');
    }

    public function test_rate_from_email_invalid_rating()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/1/1/invalid_rating?email=customer@test.com');
    }

    public function test_rate_from_email_no_email()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/' . $conversation->id . '/1/great');
    }

    public function test_rate_from_email_wrong_customer()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/' . $conversation->id . '/1/great?email=wrong@test.com');
    }

    public function test_rate_from_email_invalid_thread()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/' . $conversation->id . '/999999/great?email=customer@test.com');
    }

    public function test_rate_from_email_no_thread()
    {
        $admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $mailbox = \App\Mailbox::first() ?? \App\Mailbox::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        SatisfactionRatingSetting::create(array_merge(SatisfactionRatingSetting::defaultValues(), [
            'mailbox_id' => $mailbox->id,
            'enabled' => true,
        ]));
        
        $customer = factory(Customer::class)->create();
        Email::create('customer@test.com', $customer->id);
        
        $conversation = factory(Conversation::class)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'state' => Conversation::STATE_PUBLISHED
        ]);

        $response = $this->get(\Helper::getSubdirectory() . 'mailbox/' . $mailbox->id . '/satisfaction-ratings/rate/' . $conversation->id . '/0/great?email=customer@test.com');
        $response->assertStatus(200);
        
        $rating = SatisfactionRating::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($rating);
        $this->assertNull($rating->thread_id);
    }
}
