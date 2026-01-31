<?php

namespace InterNACHI\Modular\Tests\Plugins;

use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;
use Livewire\Mechanisms\ComponentRegistry;

class LivewirePluginTest extends TestCase
{
	use PreloadsAppModules;

	protected function setUp(): void
	{
		if (! class_exists(\Livewire\LivewireServiceProvider::class)) {
			$this->markTestSkipped('Livewire is not installed.');
		}

		parent::setUp();
	}

	protected function getPackageProviders($app): array
	{
		return array_merge(parent::getPackageProviders($app), [
			\Livewire\LivewireServiceProvider::class,
		]);
	}

	public function test_livewire_component_is_registered(): void
	{
		$registry = $this->app->make(ComponentRegistry::class);

		$class = $registry->getClass('test-module::counter');

		$this->assertEquals(\Modules\TestModule\Livewire\Counter::class, $class);
	}
}
