<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;

class Laravel11EventDiscoveryTest extends TestCase
{
	use PreloadsAppModules;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		$this->beforeApplicationDestroyed(fn() => $this->artisan('event:clear'));
		$this->requiresLaravelVersion('11.0.0');
	}
	
	public function test_it_auto_discovers_event_listeners(): void
	{
		$module = Modules::module('test-module');
		
		$this->assertNotEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
		
		// Also check that the events are cached correctly
		
		$this->artisan('event:cache');
		
		$cache = require $this->app->getCachedEventsPath();
		$cached_listeners = collect($cache)->reduce(fn(array $listeners, $row) => array_merge_recursive($listeners, $row), []);
		
		$this->assertArrayHasKey($module->qualify('Events\\TestEvent'), $cached_listeners);
		
		$this->assertContains(
			$module->qualify('Listeners\\TestEventListener').'@handle',
			$cached_listeners[$module->qualify('Events\\TestEvent')]
		);
	}
	
	protected function getPackageProviders($app)
	{
		return array_merge([EventServiceProvider::class], parent::getPackageProviders($app));
	}
}
