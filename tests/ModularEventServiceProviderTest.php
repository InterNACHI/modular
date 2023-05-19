<?php

namespace InterNACHI\Modular\Tests {
	
	use App\Tests\ModularEventSeviceProviderTest\ForceEventDiscoveryProvider;
	use Illuminate\Support\Facades\Event;
	use InterNACHI\Modular\Support\ModularEventServiceProvider;
	use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
	
	class ModularEventServiceProviderTest extends TestCase
	{
		use WritesToAppFilesystem;
		
		public function test_it_discovers_event_listeners(): void
		{
			$module = $this->makeModule();
			
			$this->artisan('make:event', ['name' => 'TestEvent', '--module' => $module->name]);
			$this->artisan('make:listener', ['name' => 'TestEventListener', '--event' => $module->qualify('Events\\TestEvent'), '--module' => $module->name]);
			
			// Because these are created after autoloading has finished, we need to manually load them
			require $module->path('src/Events/TestEvent.php');
			require $module->path('src/Listeners/TestEventListener.php');
			
			$this->app->register(new ForceEventDiscoveryProvider($this->app));
			$this->app->register(new ModularEventServiceProvider($this->app), true);
			
			$this->assertNotEmpty(Event::getListeners($module->qualify('Events\\TestEvent')));
			
			// Also check that the events are cached correctly
			
			$this->artisan('event:cache');
			
			$cache = require $this->app->getCachedEventsPath();
			
			$this->assertArrayHasKey($module->qualify('Events\\TestEvent'), $cache[ModularEventServiceProvider::class]);
			
			$this->assertContains(
				$module->qualify('Listeners\\TestEventListener@handle'), 
				$cache[ModularEventServiceProvider::class][$module->qualify('Events\\TestEvent')]
			);
			
			$this->artisan('event:clear');
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
