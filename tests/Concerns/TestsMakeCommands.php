<?php

namespace InterNACHI\Modular\Tests\Concerns;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;

trait TestsMakeCommands
{
	protected function assertMakeCommandResults(string $command, array $arguments, string $expected_path, array $expected_substrings)
	{
		$module_name = 'test-module';
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		]);
		
		$this->artisan($command, array_merge([
			'--module' => $module_name,
		], $arguments));
		
		$full_path = $this->getModulePath($module_name, $expected_path);
		
		$this->assertFileExists($full_path);
		
		$contents = $this->filesystem()->get($full_path);
		
		foreach ($expected_substrings as $substring) {
			$this->assertStringContainsString($substring, $contents);
		}
	}
}
