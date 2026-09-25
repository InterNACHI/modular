<?php

namespace InterNACHI\Modular\Plugins;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Attributes\AfterResolving;
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
			if (method_exists($this->translator, 'addNamespace')) {
				$this->translator->addNamespace($row['namespace'], $row['path']);
			}
			
			if (method_exists($this->translator, 'addJsonPath')) {
				$this->translator->addJsonPath($row['path']);
			}
		});
	}
}
