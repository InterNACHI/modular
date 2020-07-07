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
	
	public function test_it_scaffolds_a_command_in_the_module() : void
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
		
		$this->assertMakeCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
