<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModuleCache;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModuleCacheTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file() : void
	{
		$this->makeModule('test-module');
		$this->makeModule('test-module-two');
		
		$this->artisan(ModuleCache::class);
		
		$expected_path = $this->getBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');
		
		$this->assertFileExists($expected_path);
		
		$cache = include $expected_path;
		
		$this->assertArrayHasKey('test-module', $cache);
		$this->assertArrayHasKey('test-module-two', $cache);
	}
}
