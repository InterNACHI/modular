<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\Autodiscovery\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

#[AfterResolving(Gate::class, parameter: 'gate')]
class GatePlugin extends Plugin
{
	public function __construct(
		protected Gate $gate
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->modelFileFinder()
			->withModuleInfo()
			->values()
			->map(function(ModuleFileInfo $file) {
				$fqcn = $file->fullyQualifiedClassName();
				$namespace = rtrim($file->module()->namespaces->first(), '\\');
				
				$candidates = [
					$namespace.'\\Policies\\'.Str::after($fqcn, 'Models\\').'Policy', // Policies/Foo/BarPolicy
					$namespace.'\\Policies\\'.Str::afterLast($fqcn, '\\').'Policy',   // Policies/BarPolicy
				];
				
				foreach ($candidates as $candidate) {
					if (class_exists($candidate)) {
						return [
							'fqcn' => $fqcn,
							'policy' => $candidate,
						];
					}
				}
				
				return null;
			})
			->filter();
	}
	
	public function handle(Collection $data): void
	{
		$data->each(fn(array $row) => $this->gate->policy($row['fqcn'], $row['policy']));
	}
}
