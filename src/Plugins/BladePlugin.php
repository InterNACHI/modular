<?php

namespace InterNACHI\Modular\Plugins;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Plugins\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

#[AfterResolving(BladeCompiler::class, parameter: 'blade')]
class BladePlugin extends Plugin
{
	public function __construct(
		protected BladeCompiler $blade
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return [
			'files' => $finders
				->bladeComponentFileFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $component) => [
					'prefix' => $component->module()->name,
					'fqcn' => $component->fullyQualifiedClassName(),
				])
				->toArray(),
			'directories' => $finders
				->bladeComponentDirectoryFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $component) => [
					'prefix' => $component->module()->name,
					'namespace' => $component->module()->qualify('View\\Components'),
				])
				->toArray(),
		];
	}
	
	public function handle(Collection $data)
	{
		foreach ($data['files'] as $config) {
			$this->blade->component($config['fqcn'], null, $config['prefix']);
		}
		
		foreach ($data['directories'] as $config) {
			$this->blade->componentNamespace($config['namespace'], $config['prefix']);
		}
	}
}
