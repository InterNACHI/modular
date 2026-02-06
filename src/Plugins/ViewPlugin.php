<?php

namespace InterNACHI\Modular\Plugins;

use Illuminate\Support\Collection;
use Illuminate\View\Factory as ViewFactory;
use InterNACHI\Modular\Plugins\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

#[AfterResolving(ViewFactory::class, parameter: 'factory')]
class ViewPlugin extends Plugin
{
	public function __construct(
		protected ViewFactory $factory,
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->viewDirectoryFinder()
			->withModuleInfo()
			->values()
			->map(fn(ModuleFileInfo $dir) => [
				'namespace' => $dir->module()->name,
				'path' => $dir->getRealPath(),
			]);
	}
	
	public function handle(Collection $data)
	{
		$data->each(fn(array $d) => $this->factory->addNamespace($d['namespace'], $d['path']));
	}
}
