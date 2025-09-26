<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use Illuminate\View\Factory as ViewFactory;
use InterNACHI\Modular\Support\AutodiscoveryHelper;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

class ViewPlugin extends Plugin
{
	protected ViewFactory $factory;
	
	public function boot(ViewFactory $factory)
	{
		$this->factory = $factory;
		
		app(AutodiscoveryHelper::class)->handle(static::class);
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
		$data->each(fn(array $row) => $this->factory->addNamespace($row['namespace'], $row['path']));
	}
}
