<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeJob;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeJobTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');
		
		$this->artisan('make:job', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}
	
	public function test_it_scaffolds_a_job_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeJob::class;
		$arguments = ['name' => 'TestJob'];
		$expected_path = 'src/Jobs/TestJob.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Jobs',
			'class TestJob',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_job_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeJob::class;
		$arguments = ['name' => 'TestJob'];
		$expected_path = 'app/Jobs/TestJob.php';
		$expected_substrings = [
			'namespace App\Jobs',
			'class TestJob',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
