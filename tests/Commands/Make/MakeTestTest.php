<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeTest;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeTestTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_test_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeTest::class;
		$arguments = ['name' => 'TestTest'];
		$expected_path = 'tests/Feature/TestTest.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Tests',
			'use Tests\TestCase',
			'class TestTest extends TestCase',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_test_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeTest::class;
		$arguments = ['name' => 'TestTest'];
		$expected_path = 'tests/Feature/TestTest.php';
		$expected_substrings = [
			'namespace Tests\Feature',
			'use Tests\TestCase',
			'class TestTest extends TestCase',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
