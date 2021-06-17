<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeCommandTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_command_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeCommand::class;
		$arguments = ['name' => 'TestCommand'];
		$expected_path = 'src/Console/Commands/TestCommand.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Console\Commands',
			'use Illuminate\Console\Command',
			'class TestCommand extends Command',
			'test-module:test',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_command_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeCommand::class;
		$arguments = ['name' => 'TestCommand'];
		$expected_path = 'app/Console/Commands/TestCommand.php';
		$expected_substrings = [
			'namespace App\Console\Commands',
			'use Illuminate\Console\Command',
			'class TestCommand extends Command',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
