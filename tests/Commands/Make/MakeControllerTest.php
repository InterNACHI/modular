<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeController;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeControllerTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_controller_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeController::class;
		$arguments = ['name' => 'TestController'];
		$expected_path = 'src/Http/Controllers/TestController.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Http\Controllers',
			'use App\Http\Controllers\Controller',
			'class TestController extends Controller',
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
			'class TestController extends Controller',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
