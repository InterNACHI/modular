<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Plugins\ModulesPlugin;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModulesCacheTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file(): void
	{
		$expected_path = $this->getApplicationBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/app-modules.php');
		
		try {
			$this->makeModule('test-module');
			$this->makeModule('test-module-two');
			
			$this->artisan(ModulesCache::class);
			
			$this->assertFileExists($expected_path);
			
			$cache = include $expected_path;
			
			$this->assertArrayHasKey(ModulesPlugin::class, $cache);
			$this->assertArrayHasKey('test-module', $cache[ModulesPlugin::class]);
			$this->assertArrayHasKey('test-module-two', $cache[ModulesPlugin::class]);
		} finally {
			if (file_exists($expected_path)) {
				unlink($expected_path);
			}
		}
	}
}
