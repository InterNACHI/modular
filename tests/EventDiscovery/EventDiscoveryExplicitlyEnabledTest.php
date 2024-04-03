<?php

// Because we need to preload files before TestBench boots the app,
// this needs to be its own isolated test file.

namespace InterNACHI\Modular\Tests\EventDiscovery {
	
	use App\EventDiscoveryExplicitlyEnabledTestProvider;
	use Illuminate\Support\Facades\Event;
	use InterNACHI\Modular\Support\Facades\Modules;
	use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
	use InterNACHI\Modular\Tests\TestCase;
	
	class EventDiscoveryExplicitlyEnabledTest extends TestCase
	{
		use PreloadsAppModules;
		
		protected function setUp(): void
		{
			parent::setUp();
			
			$this->beforeApplicationDestroyed(fn() => $this->artisan('event:clear'));
		}
		
		public function test_it_auto_discovers_event_listeners(): void
		{
			$module = Modules::module('test-module');
			
			$this->assertNotEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
			
			// Also check that the events are cached correctly
			
			$this->artisan('event:cache');
			
			$cache = require $this->app->getCachedEventsPath();
			
			$this->assertArrayHasKey($module->qualify('Events\\TestEvent'), $cache[EventDiscoveryExplicitlyEnabledTestProvider::class]);
			
			$this->assertContains(
				$module->qualify('Listeners\\TestEventListener@handle'),
				$cache[EventDiscoveryExplicitlyEnabledTestProvider::class][$module->qualify('Events\\TestEvent')]
			);
			
			$this->artisan('event:clear');
		}
		
		protected function getPackageProviders($app)
		{
			return array_merge([EventDiscoveryExplicitlyEnabledTestProvider::class], parent::getPackageProviders($app));
		}
		
		protected function resolveApplicationConfiguration($app)
		{
			parent::resolveApplicationConfiguration($app);
			
			$app['config']['app-modules.should_discover_events'] = true;
		}
	}
}

// We need to use an "App" namespace to tell modular that this provider should be deferred to

namespace App {
	
	use Illuminate\Foundation\Support\Providers\EventServiceProvider;
	
	class EventDiscoveryExplicitlyEnabledTestProvider extends EventServiceProvider
	{
		public function shouldDiscoverEvents()
		{
			return false;
		}
	}
}
