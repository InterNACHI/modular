<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;
use Livewire\Livewire;

class ModulesCacheTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file(): void
	{
		$this->makeModule('test-module');
		$this->makeModule('test-module-two');
		
		$this->artisan(ModulesCache::class);
		
		$expected_path = $this->getBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');
		
		$this->assertFileExists($expected_path);
		
		$cache = include $expected_path;
		
		$this->assertArrayHasKey('modules', $cache);
		$this->assertArrayHasKey('blade_components', $cache);
		$this->assertArrayHasKey('commands', $cache);
		$this->assertArrayHasKey('migrations', $cache);
		$this->assertArrayHasKey('models', $cache);
		$this->assertArrayHasKey('routes', $cache);
		$this->assertArrayHasKey('view_directories', $cache);
		$this->assertArrayHasKey('lang_directories', $cache);
		
		if (class_exists(Livewire::class)) {
			$this->assertArrayHasKey('livewire_components', $cache);
		}
		
		if (version_compare($this->app->version(), '8.0.0', '<')) {
			$this->assertArrayHasKey('legacy_factories', $cache);
		}
		
		$this->assertArrayHasKey('test-module', $cache['modules']);
		$this->assertArrayHasKey('test-module-two', $cache['modules']);
	}
}
