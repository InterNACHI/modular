<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Exceptions\CannotFindModuleForPathException;
use Symfony\Component\Finder\SplFileInfo;

class ModuleRegistry
{
	protected ?Collection $modules = null;
	
	protected string $modules_real_path;
	
	public function __construct(
		protected string $modules_path,
		protected string $cache_path
	) {
		$this->modules_real_path = realpath($this->modules_path);
	}
	
	public function getModulesPath(): string
	{
		return $this->modules_path;
	}
	
	public function getCachePath(): string
	{
		return $this->cache_path;
	}
	
	public function module(string $name = null): ?ModuleConfig
	{
		// We want to allow for gracefully handling empty/null names
		return $name
			? $this->modules()->get($name)
			: null;
	}
	
	public function moduleForPath(string $path): ?ModuleConfig
	{
		return $this->module($this->extractModuleNameFromPath($path));
	}
	
	public function moduleForPathOrFail(string $path): ModuleConfig
	{
		if ($module = $this->moduleForPath($path)) {
			return $module;
		}
		
		throw new CannotFindModuleForPathException($path);
	}
	
	public function moduleForClass(string $fqcn): ?ModuleConfig
	{
		return $this->modules()->first(function(ModuleConfig $module) use ($fqcn) {
			foreach ($module->namespaces as $namespace) {
				if (Str::startsWith($fqcn, $namespace)) {
					return true;
				}
			}
			
			return false;
		});
	}
	
	public function modules(): Collection
	{
		return $this->modules ??= $this->loadModules();
	}
	
	public function reload(): Collection
	{
		$this->modules = null;
		
		return $this->loadModules();
	}
	
	protected function loadModules(): Collection
	{
		if (file_exists($this->cache_path)) {
			return Collection::make(require $this->cache_path)
				->mapWithKeys(function(array $cached) {
					$config = new ModuleConfig($cached['name'], $cached['base_path'], new Collection($cached['namespaces']));
					return [$config->name => $config];
				});
		}
		
		if (! is_dir($this->modules_path)) {
			return new Collection();
		}
		
		return FinderCollection::forFiles()
			->depth('== 1')
			->name('composer.json')
			->in($this->modules_path)
			->collect()
			->mapWithKeys(function(SplFileInfo $path) {
				$config = ModuleConfig::fromComposerFile($path);
				return [$config->name => $config];
			});
	}
	
	protected function extractModuleNameFromPath(string $path): string
	{
		// Handle Windows-style paths
		$path = str_replace('\\', '/', $path);
		
		$modules_path = str_replace('\\', '/', $this->modules_path);
		$modules_real_path = str_replace('\\', '/', $this->modules_real_path);
		
		$prefix = str_starts_with($path, $modules_real_path)
			? $modules_real_path
			: $modules_path;
		
		$relative_path = trim(Str::after($path, $prefix), '/');
		$segments = explode('/', $relative_path);
		
		dump([
			sprintf('path = %s', $path),
			sprintf('modules_path = %s', $modules_path),
			sprintf('modules_real_path = %s', $modules_real_path),
			sprintf('prefix = %s', $prefix),
			sprintf('relative_path = %s', $relative_path),
			sprintf('name = %s', $segments[0]),
		]);
		
		return $segments[0];
	}
}
