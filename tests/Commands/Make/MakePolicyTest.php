<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakePolicy;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakePolicyTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_policy_in_the_module_when_module_option_is_set(): void
	{
		$command = MakePolicy::class;
		$arguments = ['name' => 'TestPolicy'];
		$expected_path = 'src/Policies/TestPolicy.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Policies',
			'class TestPolicy',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_policy_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakePolicy::class;
		$arguments = ['name' => 'TestPolicy'];
		$expected_path = 'app/Policies/TestPolicy.php';
		$expected_substrings = [
			'namespace App\Policies',
			'class TestPolicy',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
