<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Container\Container;
use Symfony\Component\Finder\SplFileInfo;

class ModuleFileInfo extends SplFileInfo
{
	protected ?ModuleConfig $module = null;
	
	public function fullyQualifiedClassName(): string
	{
		return $this->module()->pathToFullyQualifiedClassName($this->getPathname());
	}
	
	public function module(): ModuleConfig
	{
		return $this->module ??= Container::getInstance()
			->make(ModuleRegistry::class)
			->moduleForPathOrFail($this->getPath());
	}
}
