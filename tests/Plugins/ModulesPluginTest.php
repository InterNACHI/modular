<?php

namespace InterNACHI\Modular\Tests\Plugins;

use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class ModulesPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_modules_are_discovered(): void
	{
		$modules = Modules::modules();
		
		$this->assertCount(1, $modules);
		$this->assertArrayHasKey('test-module', $modules);
	}
	
	public function test_module_config_is_populated(): void
	{
		$module = Modules::module('test-module');
		
		$this->assertInstanceOf(ModuleConfig::class, $module);
		$this->assertEquals('test-module', $module->name);
		$this->assertStringEndsWith('/app-modules/test-module', $module->base_path);
	}
	
	public function test_namespaces_are_extracted_from_composer_json(): void
	{
		$module = Modules::module('test-module');
		
		$this->assertNotEmpty($module->namespaces);
		$this->assertContains('Modules\\TestModule\\', $module->namespaces->values()->all());
	}
	
	public function test_qualify_method_works(): void
	{
		$module = Modules::module('test-module');
		
		$this->assertEquals(
			'Modules\\TestModule\\Models\\User',
			$module->qualify('Models\\User')
		);
	}
}
