<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;
use Symfony\Component\Finder\SplFileInfo;

/** @mixin SplFileInfo */
class ModuleFileInfo
{
	use ForwardsCalls;
	
	protected ?ModuleConfig $module = null;
	
	public function __construct(
		protected SplFileInfo $file,
	) {
	}
	
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
	
	public function __call(string $name, array $arguments)
	{
		return $this->forwardDecoratedCallTo($this->file, $name, $arguments);
	}
}
