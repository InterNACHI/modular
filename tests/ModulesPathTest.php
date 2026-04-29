<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Support\Facades\Config;

class ModulesPathTest extends TestCase
{
    public function testModulesPathWithoutArgument()
    {
        Config::set('app-modules.modules_directory', 'app-modules');

        $expected = str_replace('\\', '/', base_path('app-modules'));

        $this->assertEquals($expected, modules_path());
    }

    public function testModulesPathWithArgument()
    {
        Config::set('app-modules.modules_directory', 'app-modules');

        $expected = str_replace('\\', '/', base_path('app-modules/module/test.php'));

        $this->assertEquals($expected, modules_path('/module/test.php'));
    }
}