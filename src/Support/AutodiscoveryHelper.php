<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\Autodiscovery\Plugin;
use InterNACHI\Modular\Support\Autodiscovery\PluginRegistry;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	protected array $handled = [];
	
	public function __construct(
		protected CacheHelper $cache,
		protected PluginRegistry $registry,
		protected FinderFactory $finders,
	) {
	}
	
	public function writeCache(): void
	{
		foreach ($this->registry->all() as $plugin) {
			$this->discover($plugin);
		}
		
		$this->cache->write($this->data);
	}
	
	public function clearCache(): void
	{
		$this->cache->clear();
		
		$this->handled = [];
		$this->data = null;
	}
	
	public function bootPlugins(Application $app): void
	{
		foreach ($this->registry->all() as $class) {
			$class::boot($this->handle(...), $app);
		}
	}
	
	/** @param class-string<Plugin> $name */
	public function discover(string $name): Collection
	{
		$this->data ??= $this->cache->read();
		$this->data[$name] ??= $this->registry->plugin($name)->discover($this->finders);
		
		return collect($this->data[$name]);
	}
	
	/** @param class-string<Plugin> $name */
	public function handle(string $name, array $parameters = []): mixed
	{
		return $this->handled[$name] ??= $this->registry->plugin($name, $parameters)->handle($this->discover($name));
	}
}
