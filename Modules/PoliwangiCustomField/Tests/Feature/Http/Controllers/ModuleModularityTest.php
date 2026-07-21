<?php

namespace Modules\PoliwangiCustomField\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;

class ModuleModularityTest extends TestCase
{
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
        $this->mailbox->users()->sync([$this->admin->id]);
        $this->actingAs($this->admin);
    }

    public function test_custom_field_page_is_inaccessible_when_module_is_disabled()
    {
        // Disable the module to simulate modularity behavior
        $module = \Module::find('PoliwangiCustomField');
        $module->disable();

        // Refresh the application so the service providers boot again without the disabled module
        $this->refreshApplication();
        $this->actingAs($this->admin);

        // Expect an error because the route should not be registered when the module is off
        $this->withExceptionHandling();
        
        // Attempt to access a route that belongs to the module
        // We use the raw URL instead of route() because route() will throw an exception immediately if not defined
        $url = '/mailboxes/' . $this->mailbox->id . '/custom-fields';
        
        $response = $this->get($url);

        // 404 Not Found proves that the module's routes are completely detached
        $response->assertStatus(404);
        
        // Re-enable the module after the test so we don't break other tests or the local environment
        $module->enable();
    }
}
