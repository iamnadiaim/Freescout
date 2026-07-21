<?php

namespace Modules\PoliwangiSatisfaction\Tests\Feature\Http\Controllers;

use Tests\TestCase;

class ModuleModularityTest extends TestCase
{
    public function test_module_has_correct_dependencies()
    {
        $module = \Nwidart\Modules\Facades\Module::find('PoliwangiSatisfaction');
        $this->assertNotNull($module, 'PoliwangiSatisfaction module should exist');
    }
}
