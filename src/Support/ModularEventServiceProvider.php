<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use ReflectionProperty;
use Symfony\Component\Finder\SplFileInfo;

class ModularEventServiceProvider extends ServiceProvider
{
	public function register()
	{
		// We need to do this in the App::booting hook to ensure that it registers
		// events before the EventServiceProvider::booting callback triggers. It's
		// necessary to modify the existing EventServiceProvider's $listen array,
		// rather than just register our own EventServiceProvider subclass, because
		// Laravel behaves differently if the non-default provider is registered.
		$this->app->booting(function() {
			$events = $this->getEvents();
			$provider = Arr::first($this->app->getProviders(EventServiceProvider::class));
			
			if (! $provider) {
				return;
			}
			
			$listen = new ReflectionProperty($provider, 'listen');
			$listen->setAccessible(true);
			$listen->setValue($provider, array_merge_recursive($listen->getValue($provider), $events));
		});
	}
	
	public function getEvents(): array
	{
		// If events are cached, or Modular event discovery is disabled, then we'll
		// just let the normal event service provider handle all the event loading.
		if ($this->app->eventsAreCached() || ! $this->shouldDiscoverEvents()) {
			return [];
		}
		
		return $this->discoverEvents();
	}
	
	public function shouldDiscoverEvents(): bool
	{
		return config('app-modules.should_discover_events')
			?? $this->appIsConfiguredToDiscoverEvents();
	}
	
	public function discoverEvents()
	{
		$modules = $this->app->make(ModuleRegistry::class);
		
		return $this->app->make(AutoDiscoveryHelper::class)
			->listenerDirectoryFinder()
			->map(fn(SplFileInfo $directory) => $directory->getPathname())
			->reduce(function($discovered, string $directory) use ($modules) {
				$module = $modules->moduleForPath($directory);
				return array_merge_recursive(
					$discovered,
					DiscoverEvents::within($directory, $module->path('src'))
				);
			}, []);
	}
	
	public function appIsConfiguredToDiscoverEvents(): bool
	{
		return collect($this->app->getProviders(EventServiceProvider::class))
			->filter(fn(EventServiceProvider $provider) => $provider::class === EventServiceProvider::class
				|| str_starts_with(get_class($provider), $this->app->getNamespace()))
			->contains(fn(EventServiceProvider $provider) => $provider->shouldDiscoverEvents());
	}
}
