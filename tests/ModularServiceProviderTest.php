<?php

namespace InterNACHI\Modular\Tests;

use InterNACHI\Modular\Support\ModuleRegistry;

class ModularServiceProviderTest extends TestCase
{
	public function test_core_modular_functionality_is_registered() : void
	{
		$registry = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleRegistry::class, $registry);
	}
}
