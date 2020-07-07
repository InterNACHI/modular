<?php

namespace InterNACHI\Modular\Tests;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;

class ModuleRegistryTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_resolves_modules() : void
	{
		$this->artisan(MakeModule::class, [
			'name' => 'test-module',
			'--accept-default-namespace' => true,
		]);
		
		$this->artisan(MakeModule::class, [
			'name' => 'test-module-two',
			'--accept-default-namespace' => true,
		]);
		
		$registry = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleConfig::class, $registry->module('test-module'));
		$this->assertInstanceOf(ModuleConfig::class, $registry->module('test-module-two'));
		$this->assertNull($registry->module('non-existant-module'));
		
		$this->assertCount(2, $registry->modules());
		
		$module = $registry->moduleForPath($this->getModulePath('test-module', 'foo/bar'));
		$this->assertInstanceOf(ModuleConfig::class, $module);
		$this->assertEquals('test-module', $module->name);
	}
}
