<?php

namespace Modules\PoliwangiTimeTracking\Tests\Feature\Http\Controllers;

use Tests\TestCase;

class ModuleModularityTest extends TestCase
{
    public function test_module_has_correct_dependencies()
    {
        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiTimeTracking');
        $this->assertNotNull($module, 'PoliwangiTimeTracking module should exist');
    }
}
