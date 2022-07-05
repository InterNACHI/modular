<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeEvent;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeEventTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->artisan('make:event', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_event_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeEvent::class;
		$arguments = ['name' => 'TestEvent'];
		$expected_path = 'src/Events/TestEvent.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Events',
			'class TestEvent',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_event_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeEvent::class;
		$arguments = ['name' => 'TestEvent'];
		$expected_path = 'app/Events/TestEvent.php';
		$expected_substrings = [
			'namespace App\Events',
			'class TestEvent',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
