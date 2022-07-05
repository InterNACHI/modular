<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeCast;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeCastTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->artisan('make:cast', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_cast_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeCast::class;
		$arguments = ['name' => 'JsonCast'];
		$expected_path = 'src/Casts/JsonCast.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Casts',
			'class JsonCast',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_cast_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeCast::class;
		$arguments = ['name' => 'JsonCast'];
		$expected_path = 'app/Casts/JsonCast.php';
		$expected_substrings = [
			'namespace App\Casts',
			'class JsonCast',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
