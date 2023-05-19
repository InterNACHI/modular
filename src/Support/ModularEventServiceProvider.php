<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Symfony\Component\Finder\SplFileInfo;

class ModularEventServiceProvider extends EventServiceProvider
{
	public function discoverEvents()
	{
		return collect($this->discoverEventsWithin())
			->reject(fn ($directory) => ! is_dir($directory))
			->reduce(fn ($discovered, $directory) => array_merge_recursive(
				$discovered,
				DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
			), []);
	}
	
	public function shouldDiscoverEvents()
	{
		// We'll enable event discovery if it's enabled in the app namespace
		return collect($this->app->getProviders(EventServiceProvider::class))
			->filter(fn (EventServiceProvider $provider) => str_starts_with(get_class($provider), $this->app->getNamespace()))
			->contains(fn (EventServiceProvider $provider) => $provider->shouldDiscoverEvents());
	}
	
	protected function discoverEventsWithin()
	{
		return $this->app->make(AutoDiscoveryHelper::class)
			->listenerDirectoryFinder()
			->map(fn (SplFileInfo $directory) => $directory->getPathname())
			->values()
			->all();
	}
}
