<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Artisan;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\Console\Commands\TestCommand;

class ArtisanPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_commands_are_registered(): void
	{
		$commands = Artisan::all();
		
		$this->assertInstanceOf(TestCommand::class, $commands['test-module:test-command']);
	}
}
