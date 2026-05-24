<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeEnum;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeEnumTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('11.0.0');
		
		$this->artisan('make:enum', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_enum_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeEnum::class;
		$arguments = ['name' => 'Status'];
		$expected_path = '/src/Status.php';
		$expected_substrings = [
			'namespace Modules\TestModule',
			'enum Status',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_enum_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeEnum::class;
		$arguments = ['name' => 'Status'];
		$expected_path = 'app/Status.php';
		$expected_substrings = [
			'namespace App',
			'enum Status',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
