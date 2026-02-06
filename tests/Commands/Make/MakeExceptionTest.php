<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeException;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeExceptionTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->artisan('make:exception', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_exception_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeException::class;
		$arguments = ['name' => 'TestException'];
		$expected_path = 'src/Exceptions/TestException.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Exceptions',
			'class TestException',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_exception_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeException::class;
		$arguments = ['name' => 'TestException'];
		$expected_path = 'app/Exceptions/TestException.php';
		$expected_substrings = [
			'namespace App\Exceptions',
			'class TestException',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
