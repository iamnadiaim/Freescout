<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\LaporPoliwangi\Models\SavedReply;

class SavedRepliesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $mailbox;


    protected function setUp(): void
{
    parent::setUp();

    \Session::start();

    $this->user = factory(User::class)->create([
        'role' => User::ROLE_USER,
    ]);

    $this->admin = factory(User::class)->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->mailbox = factory(Mailbox::class)->create();

    $this->mailbox->users()->sync([
        $this->admin->id
    ]);

    $this->actingAs($this->admin);
}


    /**
     * INDEX
     */
    public function test_index_saved_reply_page_success()
    {
        SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Test Reply',
            'reply' => 'Hello',
            'user_id' => $this->admin->id
        ]);


        $response = $this->get(
            route('laporpoliwangi.saved_replies', [
                'id' => $this->mailbox->id
            ])
        );


        $response->assertStatus(200);


        $response->assertViewHas([
            'mailbox',
            'saved_replies',
            'parents'
        ]);
    }



    /**
     * STORE
     */
    public function test_store_saved_reply_success()
    {
        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', [
                'id' => $this->mailbox->id
            ]),
            [
                'name' => 'Greeting',
                'reply' => 'Hello customer',
                'is_global' => 1
            ]
        );


        $response->assertRedirect();


        $this->assertDatabaseHas(
            'saved_replies',
            [
                'name' => 'Greeting',
                'reply' => 'Hello customer'
            ]
        );
    }



    /**
     * STORE VALIDATION
     */
    public function test_store_saved_reply_validation_failed()
    {
        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', [
                'id' => $this->mailbox->id
            ]),
            [
                'name' => ''
            ]
        );


        $response->assertSessionHasErrors([
            'name'
        ]);
    }



    /**
     * CHILD SAVED REPLY
     */
    public function test_store_child_saved_reply_success()
    {

        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Category',
            'reply' => null,
            'user_id' => $this->admin->id
        ]);


        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', [
                'id' => $this->mailbox->id
            ]),
            [
                'name' => 'Child',
                'reply' => 'Child Reply',
                'parent_id' => $parent->id
            ]
        );


        $response->assertRedirect();


        $this->assertDatabaseHas(
            'saved_replies',
            [
                'name' => 'Child',
                'parent_id' => $parent->id
            ]
        );
    }




    /**
     * CHILD WITHOUT REPLY
     */
    public function test_store_child_without_reply_failed()
    {

        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Category',
            'user_id' => $this->admin->id
        ]);


        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', [
                'id' => $this->mailbox->id
            ]),
            [
                'name' => 'Child',
                'parent_id' => $parent->id
            ]
        );


        $response->assertSessionHasErrors([
            'reply'
        ]);
    }




    /**
     * INVALID PARENT
     */
    public function test_store_invalid_parent_failed()
    {

        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', [
                'id' => $this->mailbox->id
            ]),
            [
                'name' => 'Child',
                'reply' => 'Reply',
                'parent_id' => 99999
            ]
        );


        $response->assertSessionHasErrors([
            'parent_id'
        ]);
    }





    /**
     * UPDATE
     */
    public function test_update_saved_reply_success()
    {

        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Old',
            'reply' => 'Old',
            'user_id' => $this->admin->id
        ]);



        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ]),
            [
                'name' => 'New',
                'reply' => 'New Reply'
            ]
        );


        $response->assertRedirect();



        $this->assertDatabaseHas(
            'saved_replies',
            [
                'id' => $reply->id,
                'name' => 'New'
            ]
        );
    }





    /**
     * UPDATE CATEGORY WITH CHILD
     */
    public function test_update_category_with_children()
    {

        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Parent',
            'reply' => 'Text',
            'user_id' => $this->admin->id
        ]);



        SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child',
            'reply' => 'Child',
            'parent_id' => $parent->id,
            'user_id' => $this->admin->id
        ]);



        $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $parent->id
            ]),
            [
                'name' => 'Updated'
            ]
        );



        $this->assertDatabaseHas(
            'saved_replies',
            [
                'id' => $parent->id,
                'reply' => null
            ]
        );
    }





    /**
     * SELF PARENT
     */
    public function test_update_cannot_use_self_parent()
    {

        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Reply',
            'reply' => 'Content',
            'user_id' => $this->admin->id
        ]);



        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ]),
            [
                'name' => 'Reply',
                'reply' => 'Content',
                'parent_id' => $reply->id
            ]
        );


        $response->assertSessionHasErrors([
            'parent_id'
        ]);
    }





    /**
     * DELETE
     */
    public function test_destroy_saved_reply_success()
    {

        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Delete',
            'reply' => 'Delete',
            'user_id' => $this->admin->id
        ]);




        $response = $this->delete(
            route('laporpoliwangi.saved_replies.destroy', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ])
        );


        $response->assertRedirect();


        $this->assertDatabaseMissing(
            'saved_replies',
            [
                'id' => $reply->id
            ]
        );
    }




    /**
     * DELETE CATEGORY + CHILD
     */
    public function test_destroy_category_delete_children()
    {
        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Parent',
            'user_id' => $this->admin->id
        ]);


        $child = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child',
            'reply' => 'Child',
            'parent_id' => $parent->id,
            'user_id' => $this->admin->id
        ]);


        $response = $this->delete(
            route('laporpoliwangi.saved_replies.destroy', [
                'id' => $this->mailbox->id,
                'reply_id' => $parent->id
            ])
        );


        $response->assertRedirect();


        $this->assertDatabaseMissing(
            'saved_replies',
            [
                'id' => $child->id
            ]
        );
    }

    /**
     * AUTHORIZATION FORBIDDEN
     */
    public function test_authorization_forbidden()
    {
        $this->withExceptionHandling();
        
        $this->mailbox->users()->attach($this->user->id);
        
        $this->actingAs($this->user);

        $response = $this->get(
            route('laporpoliwangi.saved_replies', ['id' => $this->mailbox->id])
        );

        $response->assertStatus(403);
    }

    /**
     * INVALID PARENT FROM ANOTHER MAILBOX
     */
    public function test_store_invalid_parent_from_another_mailbox()
    {
        $this->withExceptionHandling();
        $otherMailbox = factory(Mailbox::class)->create();
        $otherParent = SavedReply::create([
            'mailbox_id' => $otherMailbox->id,
            'name' => 'Other Parent',
            'user_id' => $this->admin->id
        ]);

        $response = $this->post(
            route('laporpoliwangi.saved_replies.store', ['id' => $this->mailbox->id]),
            [
                'name' => 'Child',
                'reply' => 'Reply',
                'parent_id' => $otherParent->id
            ]
        );

        $response->assertSessionHasErrors(['parent_id']);
    }

    /**
     * UPDATE VALIDATION FAILED
     */
    public function test_update_validation_failed()
    {
        $this->withExceptionHandling();
        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Valid Name',
            'reply' => 'Valid Content',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ]),
            [
                'name' => ''
            ]
        );

        $response->assertSessionHasErrors(['name']);
    }

    /**
     * UPDATE: PARENT BECOMES CATEGORY
     */
    public function test_update_parent_becomes_category()
    {
        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Parent',
            'reply' => 'Initial Parent Content',
            'user_id' => $this->admin->id
        ]);

        $child = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child',
            'reply' => 'Child Content',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $child->id
            ]),
            [
                'name' => 'Child',
                'reply' => 'Child Content',
                'parent_id' => $parent->id
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('saved_replies', [
            'id' => $parent->id,
            'reply' => null
        ]);
    }

    /**
     * UPDATE: SAVED REPLY WITH CHILDREN STAYS CATEGORY
     */
    public function test_update_reply_with_children_stays_category()
    {
        $category = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Category',
            'reply' => null,
            'user_id' => $this->admin->id
        ]);

        $child = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child',
            'reply' => 'Child Content',
            'parent_id' => $category->id,
            'user_id' => $this->admin->id
        ]);

        $otherParent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Other Parent',
            'reply' => 'Other Parent Content',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $category->id
            ]),
            [
                'name' => 'Updated Category',
                'reply' => 'Should Not Save',
                'parent_id' => $otherParent->id
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('saved_replies', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'parent_id' => null,
            'reply' => null
        ]);
    }

    /**
     * AUTHORIZATION NOT AUTHENTICATED
     */
    public function test_authorization_not_logged_in_without_middleware()
    {
        $this->withExceptionHandling();
        $this->withoutMiddleware();
        auth()->logout();

        $response = $this->get(
            route('laporpoliwangi.saved_replies', ['id' => $this->mailbox->id])
        );

        $response->assertStatus(403);
    }

    /**
     * AUTHORIZATION FORBIDDEN NO MAILBOX ACCESS
     */
    public function test_authorization_no_mailbox_access()
    {
        $this->withExceptionHandling();
        
        $otherUser = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);
        
        $this->actingAs($otherUser);

        $response = $this->get(
            route('laporpoliwangi.saved_replies', ['id' => $this->mailbox->id])
        );

        $response->assertStatus(403);
    }

    /**
     * UPDATE NOT FOUND
     */
    public function test_update_not_found()
    {
        $this->withExceptionHandling();

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => 99999
            ]),
            [
                'name' => 'Valid',
                'reply' => 'Content'
            ]
        );

        $response->assertStatus(404);
    }

    /**
     * UPDATE INVALID PARENT FROM ANOTHER MAILBOX
     */
    public function test_update_invalid_parent_from_another_mailbox()
    {
        $this->withExceptionHandling();
        $otherMailbox = factory(Mailbox::class)->create();
        $otherParent = SavedReply::create([
            'mailbox_id' => $otherMailbox->id,
            'name' => 'Other Parent',
            'user_id' => $this->admin->id
        ]);

        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Valid Name',
            'reply' => 'Valid Content',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ]),
            [
                'name' => 'Updated Name',
                'reply' => 'Content',
                'parent_id' => $otherParent->id
            ]
        );

        $response->assertSessionHasErrors(['parent_id']);
    }

    /**
     * UPDATE CHILD WITHOUT REPLY
     */
    public function test_update_child_without_reply()
    {
        $this->withExceptionHandling();
        $parent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Parent',
            'user_id' => $this->admin->id
        ]);

        $reply = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child',
            'reply' => 'Old Content',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $reply->id
            ]),
            [
                'name' => 'Child',
                'reply' => '', 
                'parent_id' => $parent->id
            ]
        );

        $response->assertSessionHasErrors(['reply']);
    }

    /**
     * UPDATE MOVE TO NEW PARENT, OLD PARENT STILL HAS CHILDREN
     */
    public function test_update_move_child_to_new_parent_old_parent_has_children()
    {
        $oldParent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Old Parent',
            'user_id' => $this->admin->id
        ]);

        $child1 = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child 1',
            'reply' => 'Content 1',
            'parent_id' => $oldParent->id,
            'user_id' => $this->admin->id
        ]);

        $child2 = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'Child 2',
            'reply' => 'Content 2',
            'parent_id' => $oldParent->id,
            'user_id' => $this->admin->id
        ]);

        $newParent = SavedReply::create([
            'mailbox_id' => $this->mailbox->id,
            'name' => 'New Parent',
            'user_id' => $this->admin->id
        ]);

        $response = $this->put(
            route('laporpoliwangi.saved_replies.update', [
                'id' => $this->mailbox->id,
                'reply_id' => $child1->id
            ]),
            [
                'name' => 'Child 1',
                'reply' => 'Content 1',
                'parent_id' => $newParent->id
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('saved_replies', [
            'id' => $oldParent->id,
            'reply' => null
        ]);
        
        $this->assertDatabaseHas('saved_replies', [
            'id' => $newParent->id,
            'reply' => null
        ]);
    }
}
