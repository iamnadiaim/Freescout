<?php

namespace Modules\PoliwangiSavedReply\Tests\Feature\Http\Controllers;

use App\Mailbox;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PoliwangiSavedReply\Models\SavedReply;
use Tests\TestCase;

class SavedRepliesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected static $savedReplyMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$savedReplyMigrated) {
            $this->artisan('migrate:fresh');
            
            // Run module migrations
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiSavedReply/Database/Migrations']);
            
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$savedReplyMigrated = true;
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

        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiSavedReply');
        if ($module) {
            $module->enable();
        }
        app()->register(\Modules\PoliwangiSavedReply\Providers\PoliwangiSavedReplyServiceProvider::class);
    }

    private function getAdminUser()
    {
        return factory(User::class)->create(['role' => User::ROLE_ADMIN]);
    }

    private function getNormalUser()
    {
        return factory(User::class)->create(['role' => User::ROLE_USER]);
    }

    private function getMailbox()
    {
        return Mailbox::first() ?? Mailbox::create(['name' => 'Test Mailbox', 'email' => 'test@test.com']);
    }

    public function test_index_unauthorized_not_logged_in()
    {
        $mailbox = $this->getMailbox();
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
    }

    public function test_index_unauthorized_no_access()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getNormalUser();
        $this->actingAs($user);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
    }

    public function test_index_unauthorized_no_permission()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getNormalUser();
        
        $mailbox->users()->attach($user->id);
        
        $this->actingAs($user);
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
    }

    public function test_index_authorized_admin()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Test Reply',
            'reply' => 'Content',
            'is_global' => false,
            'user_id' => $user->id
        ]);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        $response->assertStatus(200);
        $response->assertViewIs('poliwangisavedreply::saved_replies');
    }

    public function test_store_validation_fails()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies', []);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    public function test_store_success()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies', [
            'name' => 'New Saved Reply',
            'reply' => 'Hello there!',
            'is_global' => 1
        ]);
        
        $response->assertRedirect(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        $this->assertDatabaseHas('saved_replies', [
            'name' => 'New Saved Reply',
            'reply' => 'Hello there!'
        ]);
    }
    
    public function test_store_with_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $parent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Parent Category',
            'reply' => 'old content',
            'user_id' => $user->id
        ]);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies', [
            'name' => 'Child Reply',
            'reply' => 'Child content',
            'parent_id' => $parent->id
        ]);
        
        $response->assertRedirect(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        $this->assertDatabaseHas('saved_replies', [
            'name' => 'Child Reply',
            'parent_id' => $parent->id
        ]);
        
        // Parent reply content should be set to null when it becomes a category
        $this->assertDatabaseHas('saved_replies', [
            'id' => $parent->id,
            'reply' => null
        ]);
    }
    
    public function test_update_success()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Old Name',
            'reply' => 'Old content',
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => 'Updated Name',
            'reply' => 'Updated content'
        ]);
        
        $response->assertRedirect(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        $this->assertDatabaseHas('saved_replies', [
            'id' => $savedReply->id,
            'name' => 'Updated Name',
            'reply' => 'Updated content'
        ]);
    }

    public function test_update_validation_fails()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Old Name',
            'reply' => 'Old content',
            'user_id' => $user->id
        ]);
        
        // name is required and min 2
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => '', // Fails validation
            'reply' => 'Updated content'
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name']);
    }
    
    public function test_destroy_success()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'To Delete',
            'reply' => 'Content',
            'user_id' => $user->id
        ]);
        
        $response = $this->delete(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id);
        $response->assertRedirect(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        
        $this->assertDatabaseMissing('saved_replies', [
            'id' => $savedReply->id
        ]);
    }

    public function test_index_authorized_normal_user_with_permission()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getNormalUser();
        $mailbox->users()->attach($user->id);
        
        \DB::table('mailbox_user')->where('mailbox_id', $mailbox->id)->where('user_id', $user->id)->update([
            'access' => json_encode([\App\Mailbox::ACCESS_PERM_EDIT])
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        $response->assertStatus(200);
    }
    
    public function test_store_invalid_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $otherMailbox = \App\Mailbox::create(['name' => 'Other Mailbox', 'email' => 'other_mailbox@test.com']);
        
        $parent = SavedReply::create([
            'mailbox_id' => $otherMailbox->id, // different mailbox
            'name' => 'Other Mailbox Parent',
            'user_id' => $user->id
        ]);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies', [
            'name' => 'Child Reply',
            'reply' => 'Child content',
            'parent_id' => $parent->id
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['parent_id']);
    }
    
    public function test_store_missing_reply_when_parent_selected()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $parent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Parent',
            'user_id' => $user->id
        ]);
        
        $response = $this->post(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies', [
            'name' => 'Child Reply',
            'reply' => '', // empty
            'parent_id' => $parent->id
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reply']);
    }
    
    public function test_update_select_itself_as_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Reply',
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => 'Reply',
            'parent_id' => $savedReply->id // selecting itself
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['parent_id']);
    }

    public function test_update_with_children_ignores_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Parent',
            'user_id' => $user->id
        ]);
        
        $child = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child',
            'parent_id' => $savedReply->id,
            'user_id' => $user->id
        ]);
        
        $otherParent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Other Parent',
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => 'Parent Updated',
            'parent_id' => $otherParent->id // try to move to other parent, but has children
        ]);
        
        $response->assertStatus(302);
        
        // Parent id should still be null
        $this->assertDatabaseHas('saved_replies', [
            'id' => $savedReply->id,
            'parent_id' => null
        ]);
    }
    
    public function test_update_invalid_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Reply',
            'user_id' => $user->id
        ]);
        
        $otherMailbox = \App\Mailbox::create(['name' => 'Other Mailbox 2', 'email' => 'other2@test.com']);

        $otherMailboxParent = SavedReply::create([
            'mailbox_id' => $otherMailbox->id,
            'name' => 'Other',
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => 'Reply',
            'parent_id' => $otherMailboxParent->id
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['parent_id']);
    }
    
    public function test_update_change_parent_cleans_up_old_parent()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $oldParent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Old Parent',
            'user_id' => $user->id
        ]);
        
        $newParent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'New Parent',
            'reply' => 'Some content',
            'user_id' => $user->id
        ]);
        
        $child = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child',
            'reply' => 'Child content',
            'parent_id' => $oldParent->id,
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $child->id, [
            'name' => 'Child',
            'reply' => 'Child content',
            'parent_id' => $newParent->id
        ]);
        
        $response->assertStatus(302);
        
        // New parent becomes a category (reply = null)
        $this->assertDatabaseHas('saved_replies', [
            'id' => $newParent->id,
            'reply' => null
        ]);
    }

    public function test_update_change_parent_old_parent_still_has_children()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $oldParent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Old Parent',
            'user_id' => $user->id
        ]);
        
        $newParent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'New Parent',
            'reply' => 'Some content',
            'user_id' => $user->id
        ]);
        
        $child1 = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child 1',
            'reply' => 'Child 1 content',
            'parent_id' => $oldParent->id,
            'user_id' => $user->id
        ]);

        $child2 = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child 2',
            'reply' => 'Child 2 content',
            'parent_id' => $oldParent->id,
            'user_id' => $user->id
        ]);
        
        // Move child1 to newParent
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $child1->id, [
            'name' => 'Child 1',
            'reply' => 'Child 1 content',
            'parent_id' => $newParent->id
        ]);
        
        $response->assertStatus(302);
        
        // Old parent should still be a category (reply = null) because child2 is still in it
        $this->assertDatabaseHas('saved_replies', [
            'id' => $oldParent->id,
            'reply' => null
        ]);
    }
    
    public function test_update_missing_reply_when_parent_selected()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $parent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Parent',
            'user_id' => $user->id
        ]);
        
        $savedReply = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child Reply',
            'user_id' => $user->id
        ]);
        
        $response = $this->put(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $savedReply->id, [
            'name' => 'Child Reply',
            'reply' => '', // empty
            'parent_id' => $parent->id
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reply']);
    }

    public function test_destroy_deletes_children()
    {
        $mailbox = $this->getMailbox();
        $user = $this->getAdminUser();
        $this->actingAs($user);
        
        $parent = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Parent',
            'user_id' => $user->id
        ]);
        
        $child = SavedReply::create([
            'mailbox_id' => $mailbox->id,
            'name' => 'Child',
            'parent_id' => $parent->id,
            'user_id' => $user->id
        ]);
        
        $response = $this->delete(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies/' . $parent->id);
        $response->assertRedirect(\Helper::getSubdirectory() . 'lapor-poliwangi/mailboxes/' . $mailbox->id . '/saved-replies');
        
        // Both parent and child should be missing
        $this->assertDatabaseMissing('saved_replies', [
            'id' => $parent->id
        ]);
        $this->assertDatabaseMissing('saved_replies', [
            'id' => $child->id
        ]);
    }
}
