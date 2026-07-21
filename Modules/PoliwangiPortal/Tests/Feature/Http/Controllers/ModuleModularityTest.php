<?php

namespace Modules\PoliwangiPortal\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use Nwidart\Modules\Facades\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleModularityTest extends TestCase
{
    use RefreshDatabase;

    protected static $portalMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$portalMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiPortal/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$portalMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    public function test_module_routes_are_not_accessible_when_module_is_disabled()
    {
        $module = Module::find('PoliwangiPortal');
        if (! $module) {
            $this->markTestSkipped('Module PoliwangiPortal not found.');
        }

        $module->disable();
        
        $this->withExceptionHandling();

        $admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        
        $this->actingAs($admin);

        $response = $this->get('/app-settings/portal');

        $response->assertStatus(404);

        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiPortal');
        if ($module) {
            $module->enable();
        }
    }
}
