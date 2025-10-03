<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Container\Container;

class PluginRegistry
{
	protected array $plugins = [];
	
	public static function instance(): self
	{
		$container = Container::getInstance();
		
		if (! $container->has(static::class)) {
			$container->instance(static::class, new self());
		}
		
		return $container->make(static::class);
	}
	
	/** @param class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin> ...$class */
	public static function register(string ...$class): void
	{
		static::instance()->add(...$class);
	}
	
	/** @param class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin> ...$class */
	public function add(string ...$class): static
	{
		foreach ($class as $fqcn) {
			$this->plugins[] = $fqcn;
		}
		
		return $this;
	}
	
	/** @return class-string<\InterNACHI\Modular\Support\Autodiscovery\Plugin>[] */
	public function all(): array
	{
		return $this->plugins;
	}
}
