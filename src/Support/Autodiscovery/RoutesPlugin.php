<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\FinderFactory;
use Symfony\Component\Finder\SplFileInfo;

class RoutesPlugin extends Plugin
{
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->routeFileFinder()
			->values()
			->map(fn(SplFileInfo $file) => $file->getRealPath());
	}
	
	public function handle(Collection $data): void
	{
		$data->each(fn(string $filename) => require $filename);
	}
}
