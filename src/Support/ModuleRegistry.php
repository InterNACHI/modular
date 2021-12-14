<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Exceptions\CannotFindModuleForPathException;

class ModuleRegistry
{
	protected AutoDiscoveryHelper $auto_discovery_helper;
	
	protected ?Collection $modules = null;
	
	protected string $modules_path;
	
	public function __construct(string $modules_path, AutoDiscoveryHelper $auto_discovery_helper)
	{
		$this->modules_path = $modules_path;
		$this->auto_discovery_helper = $auto_discovery_helper;
	}
	
	public function module(?string $name = null): ?ModuleConfig
	{
		return $this->modules()->get($name);
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
		return $this->modules()
			->first(fn(ModuleConfig $module) => Str::startsWith($fqcn, $module->namespaces->values()->all()));
	}
	
	public function modules(): Collection
	{
		return $this->modules ??= $this->loadModules();
	}
	
	public function clear(): self
	{
		$this->modules = null;
		$this->auto_discovery_helper->clearCache();
		
		return $this;
	}
	
	protected function loadModules(): Collection
	{
		return $this->auto_discovery_helper
			->getModules()
			->map(fn(array $data) => ModuleConfig::fromArray($data))
			->toBase();
	}
	
	protected function extractModuleNameFromPath(string $path): string
	{
		$relative_path = trim(Str::after($path, $this->modules_path), DIRECTORY_SEPARATOR);
		
		$segments = explode(DIRECTORY_SEPARATOR, $relative_path);
		return $segments[0];
	}
}
