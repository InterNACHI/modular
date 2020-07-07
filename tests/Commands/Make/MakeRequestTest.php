<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeRequest;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeRequestTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_request_in_the_module_when_module_option_is_set() : void
	{
		$command = MakeRequest::class;
		$arguments = ['name' => 'TestRequest'];
		$expected_path = 'src/Http/Requests/TestRequest.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Http\Requests',
			'class TestRequest',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_request_in_the_app_when_module_option_is_missing() : void
	{
		$command = MakeRequest::class;
		$arguments = ['name' => 'TestRequest'];
		$expected_path = 'app/Http/Requests/TestRequest.php';
		$expected_substrings = [
			'namespace App\Http\Requests',
			'class TestRequest',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
