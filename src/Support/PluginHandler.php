<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Foundation\Application;
use InterNACHI\Modular\PluginRegistry;
use InterNACHI\Modular\Support\Autodiscovery\Plugin;

class PluginHandler
{
	protected array $handled = [];
	
	public function __construct(
		protected PluginRegistry $registry,
		protected PluginDataRepository $data,
	) {
	}
	
	public function boot(Application $app): void
	{
		foreach ($this->registry->all() as $class) {
			$class::boot($this->handle(...), $app);
		}
	}
	
	/** @param class-string<Plugin> $name */
	public function handle(string $name, array $parameters = []): mixed
	{
		return $this->handled[$name] ??= $this->registry->get($name, $parameters)->handle($this->data->get($name));
	}
	
	public function reset(): void
	{
		$this->handled = [];
	}
}
