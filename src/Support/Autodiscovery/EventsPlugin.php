<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\AutodiscoveryHelper;
use InterNACHI\Modular\Support\DiscoverEvents;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

class EventsPlugin extends Plugin
{
	protected Application $app;
	
	protected Dispatcher $events;
	
	protected Repository $config;
	
	public function boot(Application $app, Dispatcher $events, Repository $config)
	{
		$this->app = $app;
		$this->events = $events;
		$this->config = $config;
		
		app(AutodiscoveryHelper::class)->handle(static::class);
	}
	
	public function handle(Collection $data): void
	{
		$data->each(function(array $listeners, string $event) {
			foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
				$this->events->listen($event, $listener);
			}
		});
	}
	
	public function discover(FinderFactory $finders): array
	{
		if (! $this->shouldDiscoverEvents()) {
			return [];
		}
		
		return $finders
			->listenerDirectoryFinder()
			->withModuleInfo()
			->reduce(function(array $discovered, ModuleFileInfo $file) {
				return array_merge_recursive(
					$discovered,
					DiscoverEvents::within($file->getPathname(), $file->module()->path('src'))
				);
			}, []);
	}
	
	protected function shouldDiscoverEvents(): bool
	{
		return $this->config->get('app-modules.should_discover_events') ?? $this->appIsConfiguredToDiscoverEvents();
	}
	
	protected function appIsConfiguredToDiscoverEvents(): bool
	{
		return collect($this->app->getProviders(EventServiceProvider::class))
			->filter(fn(EventServiceProvider $provider) => $provider::class === EventServiceProvider::class
				|| str_starts_with(get_class($provider), $this->app->getNamespace()))
			->contains(fn(EventServiceProvider $provider) => $provider->shouldDiscoverEvents());
	}
}
