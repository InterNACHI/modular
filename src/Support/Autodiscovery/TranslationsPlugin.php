<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Support\AutodiscoveryHelper;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;

class TranslationsPlugin extends Plugin
{
	protected Translator $translator;
	
	public function boot(TranslatorContract $translator)
	{
		if ($translator instanceof Translator) {
			$this->translator = $translator;

			app(AutodiscoveryHelper::class)->handle(static::class);
		}
	}
	
	public function handle(Collection $data): void
	{
		$data->each(function(array $row) {
			$this->translator->addNamespace($row['namespace'], $row['path']);
			$this->translator->addJsonPath($row['path']);
		});
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
}
