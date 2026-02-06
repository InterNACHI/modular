<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\View;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class ViewPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_view_namespace_is_registered(): void
	{
		$this->assertTrue(View::exists('test-module::index'));
	}
	
	public function test_view_can_be_rendered(): void
	{
		$content = View::make('test-module::index')->render();
		
		$this->assertStringContainsString('hello world', $content);
	}
	
	public function test_view_finder_has_module_namespace(): void
	{
		$finder = $this->app['view']->getFinder();
		$hints = $finder->getHints();
		
		$this->assertArrayHasKey('test-module', $hints);
	}
}
