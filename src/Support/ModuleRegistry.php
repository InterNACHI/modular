<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class ModuleRegistry
{
	/**
	 * @var Collection
	 */
	protected $modules;
	
	/**
	 * This is the base path that all modules are installed in
	 *
	 * @var string
	 */
	protected $modules_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\AutoDiscoveryHelper
	 */
	protected $auto_discovery;
	
	public function __construct(string $modules_path, AutoDiscoveryHelper $auto_discovery)
	{
		$this->modules_path = $modules_path;
		$this->auto_discovery = $auto_discovery;
	}
	
	public function getModulesPath(): string
	{
		return $this->modules_path;
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
			$this->modules = $this->auto_discovery->modules()
				->mapWithKeys(function(array $module) {
					return [
						$module['name'] => new ModuleConfig($module['name'], $module['base_path'], new Collection($module['namespaces']))
					];
				});
		}
		
		return $this->modules;
	}
	
	public function reload(): Collection
	{
		return $this->clear()->modules();
	}
	
	public function clear(): self
	{
		$this->modules = null;
		
		return $this;
	}
	
	protected function extractModuleNameFromPath(string $path): string
	{
		return (string) Str::of($path)
			->after($this->modules_path)
			->trim(DIRECTORY_SEPARATOR)
			->before(DIRECTORY_SEPARATOR);
	}
}
