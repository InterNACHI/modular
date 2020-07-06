<?php

namespace InterNACHI\Modular\Tests;

use InterNACHI\Modular\Support\ModuleRegistry;

class ModularServiceProviderTest extends TestCase
{
	public function test_core_modular_functionality_is_registered() : void
	{
		$registry = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleRegistry::class, $registry);
		
		// TODO: This worked when modular was a module itself, but needs to be refactored
		
		// $core_module = $registry->module('modular');
		//
		// $this->assertInstanceOf(ModuleConfig::class, $core_module);
		// $this->assertEquals('modular', $core_module->name);
	}
}
