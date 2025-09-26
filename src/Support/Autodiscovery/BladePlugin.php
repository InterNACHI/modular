<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Support\AutodiscoveryHelper;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

class BladePlugin extends Plugin
{
	protected BladeCompiler $blade;
	
	public function boot(BladeCompiler $blade)
	{
		$this->blade = $blade;
		
		app(AutodiscoveryHelper::class)->handle(static::class);
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
