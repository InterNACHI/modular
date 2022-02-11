<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Exceptions\CannotFindModuleForPathException;
use Symfony\Component\Finder\SplFileInfo;

class ModuleRegistry
{
	/**
	 * @var Collection
	 */
	protected $modules;
	
	/**
	 * @var string
	 */
	protected $cache_path;
	
	/**
	 * This is the base path that all modules are installed in
	 *
	 * @var string
	 */
	protected $modules_path;
	
	public function __construct(string $modules_path, string $cache_path)
	{
		$this->modules_path = $modules_path;
		$this->cache_path = $cache_path;
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
		if (null === $this->modules) {
			$this->modules = $this->loadModules();
		}
		
		return $this->modules;
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
		$relative_path = trim(Str::after($path, $this->modules_path), DIRECTORY_SEPARATOR);
		
		$segments = explode(DIRECTORY_SEPARATOR, $relative_path);
		return $segments[0];
	}
}
