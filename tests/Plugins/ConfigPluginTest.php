<?php

namespace InterNACHI\Modular\Tests\Plugins;

use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class ConfigPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_module_config_is_loaded(): void
	{
		$this->assertEquals('test_value', config('test-module.test_key'));
	}
	
	public function test_nested_config_values_are_accessible(): void
	{
		$this->assertEquals('nested_value', config('test-module.nested.key'));
	}
}
