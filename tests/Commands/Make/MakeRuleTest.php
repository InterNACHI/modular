<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeRule;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeRuleTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->artisan('make:rule', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_rule_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeRule::class;
		$arguments = ['name' => 'TestRule'];
		$expected_path = 'src/Rules/TestRule.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Rules',
			'class TestRule',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_rule_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeRule::class;
		$arguments = ['name' => 'TestRule'];
		$expected_path = 'app/Rules/TestRule.php';
		$expected_substrings = [
			'namespace App\Rules',
			'class TestRule',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
