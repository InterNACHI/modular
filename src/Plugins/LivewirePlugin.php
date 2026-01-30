<?php

namespace InterNACHI\Modular\Plugins;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Plugins\Attributes\OnBoot;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleFileInfo;
use Livewire\LivewireManager;

#[OnBoot]
class LivewirePlugin extends Plugin
{
	public function __construct(
		protected LivewireManager $livewire,
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->livewireComponentFileFinder()
			->withModuleInfo()
			->values()
			->map(fn(ModuleFileInfo $file) => [
				'name' => sprintf(
					'%s::%s',
					$file->module()->name,
					Str::of($file->getRelativePath())
						->explode('/')
						->filter()
						->push($file->getBasename('.php'))
						->map([Str::class, 'kebab'])
						->implode('.')
				),
				'fqcn' => $file->fullyQualifiedClassName(),
			]);
	}
	
	public function handle(Collection $data): void
	{
		$data->each(fn(array $d) => $this->livewire->component($d['name'], $d['fqcn']));
	}
}
