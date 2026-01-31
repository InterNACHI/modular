<?php

// Because we need to preload files before TestBench boots the app,
// this needs to be its own isolated test file.

namespace InterNACHI\Modular\Tests\EventDiscovery {
	
	use App\EventDiscoveryExplicitlyDisabledTestProvider;
	use Illuminate\Support\Facades\Event;
	use InterNACHI\Modular\Support\Facades\Modules;
	use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
	use InterNACHI\Modular\Tests\TestCase;
	
	class EventDiscoveryExplicitlyDisabledTest extends TestCase
	{
		use PreloadsAppModules;
		
		protected function setUp(): void
		{
			parent::setUp();
			
			$this->beforeApplicationDestroyed(fn() => $this->artisan('event:clear'));
		}
		
		public function test_it_does_not_auto_discover_event_listeners(): void
		{
			$module = Modules::module('test-module');
			
			$this->assertEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
			
			// Also check that the events are cached correctly
			
			$this->artisan('event:cache');
			
			$cache = require $this->app->getCachedEventsPath();
			
			$this->assertEmpty($cache[EventDiscoveryExplicitlyDisabledTestProvider::class]);
			
			$this->artisan('event:clear');
		}
		
		protected function getPackageProviders($app)
		{
			return array_merge([EventDiscoveryExplicitlyDisabledTestProvider::class], parent::getPackageProviders($app));
		}
		
		protected function resolveApplicationConfiguration($app)
		{
			parent::resolveApplicationConfiguration($app);
			
			$app['config']['app-modules.should_discover_events'] = false;
		}
	}
}

// We need to use an "App" namespace to tell modular that this provider should be deferred to

namespace App {
	
	use Illuminate\Foundation\Support\Providers\EventServiceProvider;
	
	class EventDiscoveryExplicitlyDisabledTestProvider extends EventServiceProvider
	{
		public function shouldDiscoverEvents()
		{
			return true;
		}
	}
}
