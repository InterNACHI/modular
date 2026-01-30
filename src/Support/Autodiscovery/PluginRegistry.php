<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Container\Container;
use InvalidArgumentException;

class PluginRegistry
{
	protected array $plugins = [];
	
	/** @param class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin> ...$class */
	public static function register(string ...$class): void
	{
		app(static::class)->add(...$class);
	}
	
	public function __construct(
		protected Container $container,
	) {
	}
	
	/** @param class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin> ...$class */
	public function add(string ...$class): static
	{
		foreach ($class as $fqcn) {
			$this->plugins[$fqcn] ??= null;
		}
		
		return $this;
	}
	
	/**
	 * @template TPlugin of Plugin
	 * @param class-string<TPlugin> $plugin
	 * @return TPlugin
	 */
	public function plugin(string $plugin, array $parameters = []): Plugin
	{
		if (! array_key_exists($plugin, $this->plugins)) {
			throw new InvalidArgumentException("The plugin '{$plugin}' has not been registered.");
		}
		
		return $this->plugins[$plugin] ??= $this->container->make($plugin, $parameters);
	}
	
	/** @return class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin>[] */
	public function all(): array
	{
		return array_keys($this->plugins);
	}
}
