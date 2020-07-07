<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModuleCache;
use InterNACHI\Modular\Console\Commands\ModuleClear;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModuleClearTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file() : void
	{
		$this->artisan(ModuleCache::class);
		
		$expected_path = $this->getBasePath().$this->normalizeDirectorySeparators('bootstrap/cache/modules.php');
		
		$this->assertFileExists($expected_path);
		
		$this->artisan(ModuleClear::class);
		
		$this->assertFileNotExists($expected_path);
	}
}
