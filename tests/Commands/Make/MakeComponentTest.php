<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeComponentTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:component', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_component_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeComponent::class;
		$arguments = ['name' => 'TestComponent'];
		$expected_path = 'src/View/Components/TestComponent.php';
		$expected_substrings = [
			'namespace Modules\TestModule\View\Components',
			'class TestComponent',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
		
		$expected_view_path = 'resources/views/components/test-component.blade.php';
		$this->assertModuleFile($expected_view_path);
	}
	
	public function test_it_scaffolds_a_component_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeComponent::class;
		$arguments = ['name' => 'TestComponent'];
		$expected_path = 'app/View/Components/TestComponent.php';
		$expected_substrings = [
			'namespace App\View\Components',
			'class TestComponent',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
		
		$expected_view_path = 'resources/views/components/test-component.blade.php';
		$this->assertBaseFile($expected_view_path);
	}
}
