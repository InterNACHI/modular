<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use InterNACHI\Modular\Support\Autodiscovery\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

#[AfterResolving(Translator::class, parameter: 'translator')]
class TranslatorPlugin extends Plugin
{
	public function __construct(
		protected Translator $translator,
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->langDirectoryFinder()
			->withModuleInfo()
			->values()
			->map(fn(ModuleFileInfo $dir) => [
				'namespace' => $dir->module()->name,
				'path' => $dir->getRealPath(),
			]);
	}
	
	public function handle(Collection $data): void
	{
		$data->each(function(array $row) {
			$this->translator->addNamespace($row['namespace'], $row['path']);
			$this->translator->addJsonPath($row['path']);
		});
	}
}
