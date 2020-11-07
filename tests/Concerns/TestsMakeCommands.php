<?php

namespace InterNACHI\Modular\Tests\Concerns;

use Closure;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;

trait TestsMakeCommands
{
	protected function assertModuleCommandResults(string $command, array $arguments, string $expected_path, array $expected_substrings)
	{
		$module_name = 'test-module';
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		])->assertExitCode(0);
		
		$this->artisan($command, array_merge([
			'--module' => $module_name,
		], $arguments))->assertExitCode(0);
		
		$this->assertModuleFile($expected_path, $expected_substrings, $module_name);
	}
	
	protected function assertBaseCommandResults(string $command, array $arguments, string $expected_path, array $expected_substrings)
	{
		$this->artisan($command, $arguments)->assertExitCode(0);
		
		$this->assertBaseFile($expected_path, $expected_substrings);
	}
	
	protected function assertModuleFile($expected_path, $expected_substrings = [], $module_name = 'test-module')
	{
		$full_path = $this->getModulePath($module_name, $expected_path);
		
		$directory = dirname($full_path);
		$files = implode(', ', glob($directory.DIRECTORY_SEPARATOR.'*') ?? []);
		
		$this->assertFileExists($full_path, "Could not find file. Files in directory: {$files}");
		
		$contents = $this->filesystem()->get($full_path);
		
		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString($substring, $contents);
		}
	}
	
	protected function assertBaseFile($expected_path, $expected_substrings = [])
	{
		$full_path = $this->getBasePath().$this->normalizeDirectorySeparators($expected_path);
		
		$directory = dirname($full_path);
		$files = implode(', ', glob($directory.DIRECTORY_SEPARATOR.'*') ?? []);
		
		$this->assertFileExists($full_path, "Could not find file. Files in directory: {$files}");
		
		$contents = $this->filesystem()->get($full_path);
		
		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString($substring, $contents);
		}
	}
}
