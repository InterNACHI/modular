<?php

namespace InterNACHI\Modular\Tests\EventDiscovery;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Console\Commands\ModulesClear;
use InterNACHI\Modular\Support\Autodiscovery\EventsPlugin;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class Laravel11EventDiscoveryImplicitlyEnabledTest extends TestCase
{
	use PreloadsAppModules;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		$this->beforeApplicationDestroyed(fn() => $this->artisan(ModulesClear::class));
		$this->requiresLaravelVersion('11.0.0');
	}
	
	public function test_it_auto_discovers_event_listeners(): void
	{
		$module = Modules::module('test-module');
		
		$this->assertNotEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
		
		// Also check that the events are cached correctly
		
		$this->artisan(ModulesCache::class);
		
		$cache = require $this->app->bootstrapPath('cache/app-modules.php');
		
		$this->assertArrayHasKey($module->qualify('Events\\TestEvent'), $cache[EventsPlugin::class]);

		$this->assertContains(
			$module->qualify('Listeners\\TestEventListener').'@handle',
			$cache[EventsPlugin::class][$module->qualify('Events\\TestEvent')]
		);
	}
	
	protected function getPackageProviders($app)
	{
		return array_merge([EventServiceProvider::class], parent::getPackageProviders($app));
	}
}
