<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeListener;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeListenerTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:listener', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_listener_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeListener::class;
		$arguments = ['name' => 'TestListener'];
		$expected_path = 'src/Listeners/TestListener.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Listeners',
			'class TestListener',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_listener_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeListener::class;
		$arguments = ['name' => 'TestListener'];
		$expected_path = 'app/Listeners/TestListener.php';
		$expected_substrings = [
			'namespace App\Listeners',
			'class TestListener',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
