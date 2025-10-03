<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleConfig;
use Symfony\Component\Finder\SplFileInfo;

class ModulesPlugin extends Plugin
{
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->moduleComposerFileFinder()
			->values()
			->mapWithKeys(function(SplFileInfo $file) {
				$composer_config = json_decode($file->getContents(), true, 16, JSON_THROW_ON_ERROR);
				$base_path = rtrim(str_replace('\\', '/', $file->getPath()), '/');
				$name = basename($base_path);
				
				return [
					$name => [
						'name' => $name,
						'base_path' => $base_path,
						'namespaces' => Collection::make($composer_config['autoload']['psr-4'] ?? [])
							->mapWithKeys(fn($src, $namespace) => ["{$base_path}/{$src}" => $namespace])
							->all(),
					],
				];
			});
	}
	
	/** @return Collection<int, ModuleConfig> */
	public function handle(Collection $data): Collection
	{
		return $data->map(fn(array $d) => new ModuleConfig($d['name'], $d['base_path'], new Collection($d['namespaces'])));
	}
}
