<?php

namespace InterNACHI\Modular\Plugins;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Attributes\OnRegister;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleRegistry;

#[OnRegister]
class ConfigPlugin extends Plugin
{
	public function __construct(
		protected Application $app,
		protected ModuleRegistry $registry
	) {
	}

	public function discover(FinderFactory $finders): iterable
	{
		return $this->registry->modules()
			->map(fn($module) => [
				'key' => $module->name,
				'path' => $module->path("config/{$module->name}.php"),
			])
			->filter(fn($row) => file_exists($row['path']));
	}

	public function handle(Collection $data): void
	{
		if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
			return;
		}

		$config = $this->app->make('config');

		$data->each(fn(array $row) => $config->set($row['key'], array_merge(
			require $row['path'],
			$config->get($row['key'], []),
		)));
	}
}
