<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Support\Autodiscovery\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

#[AfterResolving(BladeCompiler::class)]
class BladePlugin extends Plugin
{
	public function __construct(
		protected BladeCompiler $blade
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->bladeComponentFileFinder()
			->withModuleInfo()
			->values()
			->map(fn(ModuleFileInfo $component) => [
				'prefix' => $component->module()->name,
				'fqcn' => $component->fullyQualifiedClassName(),
			]);
	}
	
	public function handle(Collection $data)
	{
		$data->each(fn(array $row) => $this->blade->component($row['fqcn'], null, $row['prefix']));
	}
}
