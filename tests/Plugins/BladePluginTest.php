<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Modules\TestModule\View\Components\Alert;

class BladePluginTest extends TestCase
{
	use PreloadsAppModules;

	public function test_blade_component_is_registered(): void
	{
		$compiler = $this->app->make(BladeCompiler::class);
		$aliases = $compiler->getClassComponentAliases();

		$this->assertArrayHasKey('test-module-alert', $aliases);
		$this->assertEquals(Alert::class, $aliases['test-module-alert']);
	}

	public function test_blade_component_can_be_rendered(): void
	{
		$html = Blade::render('<x-test-module::alert type="warning">Test message</x-test-module::alert>');

		$this->assertStringContainsString('alert-warning', $html);
		$this->assertStringContainsString('Test message', $html);
	}
}
