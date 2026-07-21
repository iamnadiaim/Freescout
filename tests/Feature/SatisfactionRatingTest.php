<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use App\Customer;
use App\Email;
use App\Conversation;
use App\Thread;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRating;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SatisfactionRatingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;
    protected $mailbox;
    protected $customer;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiSatisfaction/Database/Migrations']);

        $this->admin = factory(User::class)->create(['role' => User::ROLE_ADMIN]);
        $this->user = factory(User::class)->create(['role' => User::ROLE_USER]);
        
        $this->mailbox = factory(Mailbox::class)->create();
        $this->mailbox->users()->sync([$this->admin->id]);

        $this->customer = factory(Customer::class)->create();
        $this->customer->syncEmails(['customer@example.com']);

        $this->conversation = factory(Conversation::class)->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'customer_email' => 'customer@example.com',
            'type' => Conversation::TYPE_EMAIL,
            'state' => Conversation::STATE_PUBLISHED,
        ]);
    }

    private function enableSettings()
    {
        SatisfactionRatingSetting::create(array_merge(
            ['mailbox_id' => $this->mailbox->id],
            SatisfactionRatingSetting::defaultValues(),
            ['enabled' => true]
        ));
    }

    // 1. Settings Authorization
    public function test_index_unauthorized()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->actingAs($this->user);
        
        $this->get(route('PoliwangiPortal.satisfaction_ratings.index', $this->mailbox->id));
    }

    public function test_index_authorized()
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('PoliwangiPortal.satisfaction_ratings.index', $this->mailbox->id));
        $response->assertStatus(200);
        $response->assertViewIs('poliwangisatisfaction::satisfaction_ratings.index');
    }

    // 2. updateSettings
    public function test_update_settings_validation_fails()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->actingAs($this->admin);
        
        $this->post(route('PoliwangiPortal.satisfaction_ratings.update_settings', $this->mailbox->id), [
            'add_ratings_mode' => 'invalid_mode'
        ]);
    }

    public function test_update_settings_success()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('PoliwangiPortal.satisfaction_ratings.update_settings', $this->mailbox->id), [
            'enabled' => '1',
            'add_ratings_mode' => 'shortcode',
            'placement' => 'below',
            'ratings_text' => 'Rate this',
            'saving_mode' => 'immediate',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('satisfaction_rating_settings', [
            'mailbox_id' => $this->mailbox->id,
            'enabled' => 1,
            'add_ratings_mode' => 'shortcode',
        ]);
    }

    // 3. updateTranslate
    public function test_update_translate_validation_fails()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->actingAs($this->admin);
        
        $this->post(route('PoliwangiPortal.satisfaction_ratings.update_translate', $this->mailbox->id), [
            'page_title' => ''
        ]);
    }

    public function test_update_translate_success()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('PoliwangiPortal.satisfaction_ratings.update_translate', $this->mailbox->id), [
            'page_title' => 'Custom Title',
            'header' => 'Header',
            'great_text' => 'Great',
            'okay_text' => 'Okay',
            'not_good_text' => 'Bad',
            'send_button_text' => 'Send',
            'send_confirmation_text' => 'Done',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('satisfaction_rating_settings', [
            'mailbox_id' => $this->mailbox->id,
            'page_title' => 'Custom Title',
        ]);
    }

    // 4. reset defaults
    public function test_reset_settings_defaults()
    {
        $this->actingAs($this->admin);
        
        $this->post(route('PoliwangiPortal.satisfaction_ratings.reset_settings', $this->mailbox->id));
        $this->assertDatabaseHas('satisfaction_rating_settings', [
            'mailbox_id' => $this->mailbox->id,
            'add_ratings_mode' => 'all', // default
        ]);
    }

    public function test_reset_translate_defaults()
    {
        $this->actingAs($this->admin);
        
        $this->post(route('PoliwangiPortal.satisfaction_ratings.reset_translate', $this->mailbox->id));
        $this->assertDatabaseHas('satisfaction_rating_settings', [
            'mailbox_id' => $this->mailbox->id,
            'page_title' => 'Satisfaction Ratings', // default
        ]);
    }

    // 5. report
    public function test_report_view()
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('PoliwangiPortal.satisfaction_ratings.report', $this->mailbox->id));
        $response->assertStatus(200);
        $response->assertViewIs('poliwangisatisfaction::satisfaction_ratings.report'); // fallback if needed
    }

    // 6. submitRating
    public function test_submit_rating_disabled()
    {
        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]));
        
        $response->assertRedirect();
        $response->assertSessionHasErrors(['rating']);
    }

    public function test_submit_rating_no_session()
    {
        $this->enableSettings();

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]));
        
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_submit_rating_validation_fails()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'customer@example.com']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]), [
            'rating' => 'invalid_rating'
        ]);
    }

    public function test_submit_rating_conversation_not_found()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'customer@example.com']);

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => 999999
        ]), [
            'rating' => 'great'
        ]);
        
        $response->assertRedirect(route('PoliwangiPortal.end_user_portal.my_ticket'));
        $response->assertSessionHasErrors(['rating']);
    }

    public function test_submit_rating_unauthorized_customer()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'other@example.com']);

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]), [
            'rating' => 'great'
        ]);
        
        $response->assertRedirect(route('PoliwangiPortal.end_user_portal.my_ticket'));
        $response->assertSessionHasErrors(['rating']);
    }

    public function test_submit_rating_thread_not_found()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'customer@example.com']);

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]), [
            'rating' => 'great',
            'thread_id' => 999999
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHasErrors(['rating']);
    }

    public function test_submit_rating_success()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'customer@example.com']);

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]), [
            'rating' => 'great',
            'comment' => 'Awesome support!'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('satisfaction_ratings', [
            'conversation_id' => $this->conversation->id,
            'email' => 'customer@example.com',
            'rating' => 'great',
            'comment' => 'Awesome support!'
        ]);
    }

    public function test_submit_rating_success_with_thread()
    {
        $this->enableSettings();
        session(['end_user_portal_email' => 'customer@example.com']);

        $thread = factory(Thread::class)->create([
            'conversation_id' => $this->conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $response = $this->post(route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id
        ]), [
            'rating' => 'okay',
            'thread_id' => $thread->id
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('satisfaction_ratings', [
            'thread_id' => $thread->id,
            'rating' => 'okay',
        ]);
    }

    // 7. rateFromEmail
    public function test_rate_from_email_disabled()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 0,
            'rating' => 'great'
        ]));
    }

    public function test_rate_from_email_invalid_rating()
    {
        $this->enableSettings();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 0,
            'rating' => 'invalid'
        ]));
    }

    public function test_rate_from_email_conversation_not_found()
    {
        $this->enableSettings();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => 999999,
            'thread_id' => 0,
            'rating' => 'great'
        ]));
    }

    public function test_rate_from_email_no_email_in_query()
    {
        $this->enableSettings();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 0,
            'rating' => 'great'
        ]));
    }

    public function test_rate_from_email_unauthorized_customer()
    {
        $this->enableSettings();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 0,
            'rating' => 'great'
        ]) . '?email=other@example.com');
    }

    public function test_rate_from_email_thread_not_found()
    {
        $this->enableSettings();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 999999,
            'rating' => 'great'
        ]) . '?email=customer@example.com');
    }

    public function test_rate_from_email_success_no_thread()
    {
        $this->enableSettings();
        $response = $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => 0,
            'rating' => 'great'
        ]) . '?email=customer@example.com');
        
        $response->assertStatus(200);
        $response->assertViewIs('poliwangisatisfaction::satisfaction_ratings.thank_you');

        $this->assertDatabaseHas('satisfaction_ratings', [
            'conversation_id' => $this->conversation->id,
            'email' => 'customer@example.com',
            'rating' => 'great',
        ]);
    }

    public function test_rate_from_email_success_with_thread()
    {
        $this->enableSettings();
        $thread = factory(Thread::class)->create([
            'conversation_id' => $this->conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $response = $this->get(\URL::signedRoute('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $this->mailbox->id,
            'conversation_id' => $this->conversation->id,
            'thread_id' => $thread->id,
            'rating' => 'not_good'
        ]) . '?email=customer@example.com');
        
        $response->assertStatus(200);

        $this->assertDatabaseHas('satisfaction_ratings', [
            'thread_id' => $thread->id,
            'rating' => 'not_good',
        ]);
    }
}
