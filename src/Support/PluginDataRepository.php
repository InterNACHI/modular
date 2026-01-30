<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Plugin;
use InterNACHI\Modular\PluginRegistry;

class PluginDataRepository
{
	public function __construct(
		protected array $data = [],
		protected PluginRegistry $registry,
		protected FinderFactory $finders,
	) {
	}
	
	public function all(): array
	{
		foreach ($this->registry->all() as $plugin) {
			$this->get($plugin);
		}
		
		return $this->data;
	}
	
	public function reset(): void
	{
		$this->data = [];
	}
	
	/** @param class-string<Plugin> $name */
	public function get(string $name): Collection
	{
		$this->data[$name] ??= $this->registry->get($name)->discover($this->finders);
		
		return collect($this->data[$name]);
	}
}
