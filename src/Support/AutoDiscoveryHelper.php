<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class AutoDiscoveryHelper
{
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $module_registry;

	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $filesystem;

	/**
	 * @var string
	 */
	protected $base_path;

	public function __construct(ModuleRegistry $module_registry, Filesystem $filesystem)
	{
		$this->module_registry = $module_registry;
		$this->filesystem = $filesystem;
		$this->base_path = $module_registry->getModulesPath();
	}

	public function commandFileFinder(): FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->name('*.php')
				->in($this->base_path.'/*/src/Console/Commands');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function factoryDirectoryFinder(): FinderCollection
	{
		try {
			return FinderCollection::forDirectories()
				->depth(0)
				->name('factories')
				->in($this->base_path.'/*/database/');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function migrationDirectoryFinder(): FinderCollection
	{
		try {
			return FinderCollection::forDirectories()
				->depth(0)
				->name('migrations')
				->in($this->base_path.'/*/database/');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function modelFileFinder(): FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->name('*.php')
				->in($this->base_path.'/*/src/Models');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function bladeComponentFileFinder(): FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->name('*.php')
				->in($this->base_path.'/*/src/View/Components');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function routeFileFinder(): FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->depth(0)
				->name('*.php')
				->in($this->base_path.'/*/routes')
				->sortByName();
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function viewDirectoryFinder(): FinderCollection
	{
		try {
			return FinderCollection::forDirectories()
				->depth(0)
				->name('views')
				->in($this->base_path.'/*/resources/');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}
	
	public function langDirectoryFinder(): FinderCollection
	{
		try {
			return FinderCollection::forDirectories()
				->depth(0)
				->name('lang')
				->in($this->base_path.'/*/resources/');
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}

	public function livewireComponentFileFinder(): FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->name('*.php')
				->in($this->base_path.'/*/src/Http/Livewire');
		} catch (\Exception $e) {
			return FinderCollection::empty();
		}
	}
}
