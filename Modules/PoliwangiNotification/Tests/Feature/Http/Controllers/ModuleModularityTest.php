<?php

namespace Modules\PoliwangiNotification\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use Nwidart\Modules\Facades\Module;

class ModuleModularityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Session::start();
        // Pastikan route utama web termuat untuk mensimulasikan booting aplikasi
        $this->app->register(\Modules\PoliwangiNotification\Providers\PoliwangiNotificationServiceProvider::class);
    }

    public function test_routes_are_not_accessible_when_module_is_disabled()
    {
        // 1. Matikan modul secara paksa
        $module = Module::find('PoliwangiNotification');
        if ($module) {
            $module->disable();
        }

        // Refresh the application so the service providers boot again without the disabled module
        $this->refreshApplication();

        // 2. Login sebagai Admin (karena route ini biasanya butuh otentikasi)
        $admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $this->actingAs($admin);
        $this->withExceptionHandling();

        // 3. Akses route modul (misalnya index)
        $response = $this->get('/notification-channels');

        // 4. Seharusnya memberikan error 404 Not Found karena modul mati, routenya tidak ter-register
        $response->assertStatus(404);

        // Nyalakan kembali modul agar tidak mengganggu test lain
        if ($module) {
            $module->enable();
        }
    }
}
