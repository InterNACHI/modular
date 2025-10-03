<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\Autodiscovery\Attributes\AfterResolving;
use InterNACHI\Modular\Support\FinderFactory;
use Symfony\Component\Finder\SplFileInfo;

#[AfterResolving(Migrator::class)]
class MigratorPlugin extends Plugin
{
	public function __construct(
		protected Migrator $migrator
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->migrationDirectoryFinder()
			->values()
			->map(fn(SplFileInfo $file) => $file->getRealPath());
	}
	
	public function handle(Collection $data): void
	{
		$data->each(fn(string $path) => $this->migrator->path($path));
	}
}
