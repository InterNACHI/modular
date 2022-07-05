<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeProvider;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeProviderTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:provider', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_provider_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeProvider::class;
		$arguments = ['name' => 'TestProvider'];
		$expected_path = 'src/Providers/TestProvider.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Providers',
			'class TestProvider',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_provider_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeProvider::class;
		$arguments = ['name' => 'TestProvider'];
		$expected_path = 'app/Providers/TestProvider.php';
		$expected_substrings = [
			'namespace App\Providers',
			'class TestProvider',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
