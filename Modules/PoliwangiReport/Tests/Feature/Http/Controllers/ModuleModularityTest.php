<?php

namespace Modules\PoliwangiReport\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModuleModularityTest extends TestCase
{
    use RefreshDatabase;

    protected static $reportMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$reportMigrated) {
            $this->artisan('migrate:fresh');
            // Jika modul report punya migrasi, dijalankan
            // \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiReport/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$reportMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    public function test_module_routes_are_not_accessible_when_module_is_disabled()
    {
        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiReport');
        if (! $module) {
            $this->markTestSkipped('Module PoliwangiReport not found.');
        }

        $module->disable();
        
        $this->withExceptionHandling();

        $admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        
        $this->actingAs($admin);

        $response = $this->get(\Helper::getSubdirectory() . 'lapor-poliwangi/time-tracking-report');

        $response->assertStatus(404);

        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiReport');
        if ($module) {
            $module->enable();
        }
    }
}
