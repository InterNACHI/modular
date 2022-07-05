<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeFactory;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeFactoryTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:factory', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_factory_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeFactory::class;
		$arguments = ['name' => 'TestFactory'];
		$expected_path = 'database/factories/TestFactory.php';
		
		if (version_compare($this->app->version(), '8.0.0', '>=')) {
			$expected_substrings = [
				'use Illuminate\Database\Eloquent\Factories\Factory;',
				'namespace Modules\TestModule\Database\Factories;',
			];
		} else {
			$expected_substrings = [
				'Illuminate\Database\Eloquent\Factory',
			];
		}
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_factory_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeFactory::class;
		$arguments = ['name' => 'TestFactory'];
		$expected_path = 'database/factories/TestFactory.php';
		
		if (version_compare($this->app->version(), '8.0.0', '>=')) {
			$expected_substrings = [
				'Illuminate\Database\Eloquent\Factories\Factory',
				'namespace Database\Factories;',
			];
		} else {
			$expected_substrings = [
				'Illuminate\Database\Eloquent\Factory',
			];
		}
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
