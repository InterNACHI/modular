<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Console\Commands\ModulesClear;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModulesClearTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file(): void
	{
		$this->artisan(ModulesCache::class);
		
		$expected_path = $this->getBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');
		
		$this->assertFileExists($expected_path);
		
		$this->artisan(ModulesClear::class);
		
		$this->assertFileNotExists($expected_path);
	}
}
