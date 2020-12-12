<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Filesystem\Filesystem;

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
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->name('*.php')
			->in($this->base_path.'/*/src/Console/Commands');
	}
	
	public function factoryDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth(0)
			->name('factories')
			->in($this->base_path.'/*/database/');
	}
	
	public function migrationDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth(0)
			->name('migrations')
			->in($this->base_path.'/*/database/');
	}
	
	public function modelFileFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->name('*.php')
			->in($this->base_path.'/*/src/Models');
	}
	
	public function bladeComponentFileFinder() : FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->name('*.php')
			->in($this->base_path.'/*/src/View/Components');
	}
	
	public function routeFileFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->depth(0)
			->name('*.php')
			->in($this->base_path.'/*/routes');
	}
	
	public function viewDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth(0)
			->name('views')
			->in($this->base_path.'/*/resources/');
	}
	
	protected function basePathMissing(): bool
	{
		return false === $this->filesystem->isDirectory($this->base_path);
	}
}
