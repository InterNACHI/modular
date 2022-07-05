<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeResource;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeResourceTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:resource', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_resource_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeResource::class;
		$arguments = ['name' => 'TestResource'];
		$expected_path = 'src/Http/Resources/TestResource.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Http\Resources',
			'class TestResource',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_resource_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeResource::class;
		$arguments = ['name' => 'TestResource'];
		$expected_path = 'app/Http/Resources/TestResource.php';
		$expected_substrings = [
			'namespace App\Http\Resources',
			'class TestResource',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
