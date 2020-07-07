<?php

namespace InterNACHI\Modular\Tests;

use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;

class ModularServiceProviderTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_registry_is_bound_as_a_singleton() : void
	{
		$registry = $this->app->make(ModuleRegistry::class);
		$registry2 = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleRegistry::class, $registry);
		$this->assertSame($registry, $registry2);
	}
}
