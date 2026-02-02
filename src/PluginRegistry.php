<?php

namespace InterNACHI\Modular;

use Illuminate\Container\Container;
use InterNACHI\Modular\Plugins\Plugin;
use InvalidArgumentException;

class PluginRegistry
{
	protected array $plugins = [];
	
	/** @param class-string<\InterNACHI\Modular\Plugins\Plugin> ...$class */
	public static function register(string ...$class): void
	{
		app(static::class)->add(...$class);
	}
	
	public function __construct(
		protected Container $container,
	) {
	}
	
	/** @param class-string<\InterNACHI\Modular\Plugins\Plugin> ...$class */
	public function add(string ...$class): static
	{
		foreach ($class as $plugin) {
			$this->plugins[$plugin] ??= true;
		}
		
		return $this;
	}
	
	/**
	 * @template TPlugin of Plugin
	 * @param class-string<TPlugin> $plugin
	 * @return TPlugin
	 */
	public function get(string $plugin, array $parameters = []): Plugin
	{
		if (! array_key_exists($plugin, $this->plugins)) {
			throw new InvalidArgumentException("The plugin '{$plugin}' has not been registered.");
		}
		
		// If we've got new parameters and the plugin has already been resolved, we'll clear it first
		if (! empty($parameters) && $this->container->resolved($plugin)) {
			$this->container->forgetInstance($plugin);
		}
		
		$plugin = $this->container->make($plugin, $parameters);
		
		// When the container resolves something with $parameters, it doesn't store that instance regardless
		// of whether it's a singleton or not. In our case, we want plugins to be stored as singletons even
		// if $parameter overrides are set, so we're going to manually save the plugin instance in the container
		$this->container->instance($plugin::class, $plugin);
		
		return $plugin;
	}
	
	/** @return class-string<\InterNACHI\Modular\Plugins\Plugin>[] */
	public function all(): array
	{
		return array_keys($this->plugins);
	}
}
