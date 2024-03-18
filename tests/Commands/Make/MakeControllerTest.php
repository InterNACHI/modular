<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeController;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;

class MakeControllerTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:controller', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_produces_an_error_if_the_module_does_not_exist(): void
	{
		$this->expectException(InvalidOptionException::class);
		$this->expectExceptionMessage('The "does-not-exist" module does not exist.');
		
		$this->artisan('make:controller', ['name' => 'Test', '--module' => 'does-not-exist']);
	}
	
	public function test_it_scaffolds_a_controller_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeController::class;
		$arguments = ['name' => 'TestController'];
		$expected_path = 'src/Http/Controllers/TestController.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Http\Controllers',
			'class TestController',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_controller_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeController::class;
		$arguments = ['name' => 'TestController'];
		$expected_path = 'app/Http/Controllers/TestController.php';
		$expected_substrings = [
			'namespace App\Http\Controllers',
			'class TestController',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
