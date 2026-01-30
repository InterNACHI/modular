<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeModelTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->artisan('make:model', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_model_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeModel::class;
		$arguments = ['name' => 'TestModel'];
		$expected_path = 'src/Models/TestModel.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Models',
			'class TestModel',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_model_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeModel::class;
		$arguments = ['name' => 'TestModel'];
		$expected_path = 'app/Models/TestModel.php';
		$expected_substrings = [
			'namespace App\Models',
			'class TestModel',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
