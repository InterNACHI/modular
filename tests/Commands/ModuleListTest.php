<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModuleCache;
use InterNACHI\Modular\Console\Commands\ModuleClear;
use InterNACHI\Modular\Console\Commands\ModuleList;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModuleListTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_writes_to_cache_file() : void
	{
		$this->makeModule('test-module');
		
		$this->artisan(ModuleList::class)
			->expectsOutput('You have 1 module installed.')
			->assertExitCode(0);
		
		$this->makeModule('test-module-two');
		
		$this->artisan(ModuleList::class)
			->expectsOutput('You have 2 modules installed.')
			->assertExitCode(0);
	}
}
