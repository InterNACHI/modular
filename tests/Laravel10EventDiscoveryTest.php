<?php

namespace InterNACHI\Modular\Tests {
	
	use App\Tests\ModularEventSeviceProviderTest\ForceEventDiscoveryProvider;
	use Illuminate\Support\Facades\Event;
	use InterNACHI\Modular\Support\Facades\Modules;
	use InterNACHI\Modular\Support\ModularEventServiceProvider;
	use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
	use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
	
	class Laravel10EventDiscoveryTest extends TestCase
	{
		use PreloadsAppModules;
		
		protected function setUp(): void
		{
			parent::setUp();
			
			$this->beforeApplicationDestroyed(fn() => $this->artisan('event:clear'));
			$this->requiresLaravelVersion('11.0.0', '<');
		}
		
		public function test_it_auto_discovers_event_listeners(): void
		{
			$module = Modules::module('test-module');
			
			$this->assertNotEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
			
			// Also check that the events are cached correctly
			
			$this->artisan('event:cache');
			
			$cache = require $this->app->getCachedEventsPath();
			
			$this->assertArrayHasKey($module->qualify('Events\\TestEvent'), $cache[ForceEventDiscoveryProvider::class]);
			
			$this->assertContains(
				$module->qualify('Listeners\\TestEventListener@handle'),
				$cache[ForceEventDiscoveryProvider::class][$module->qualify('Events\\TestEvent')]
			);
			
			$this->artisan('event:clear');
		}
		
		protected function getPackageProviders($app)
		{
			return array_merge([ForceEventDiscoveryProvider::class], parent::getPackageProviders($app));
		}
	}
}

// We need to use an "App" namespace to tell modular that this provider should be deferred to

namespace App\Tests\ModularEventSeviceProviderTest {
	
	use Illuminate\Foundation\Support\Providers\EventServiceProvider;
	
	class ForceEventDiscoveryProvider extends EventServiceProvider
	{
		public function shouldDiscoverEvents()
		{
			return true;
		}
	}
}
