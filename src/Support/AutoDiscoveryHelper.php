<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;

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
	
	public function __construct(ModuleRegistry $module_registry, Filesystem $filesystem, string $base_path)
	{
		$this->module_registry = $module_registry;
		$this->filesystem = $filesystem;
		$this->base_path = $base_path;
	}
	
	public function commandFileFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->depth('> 3')
			->path('src/Console/Commands')
			->name('*.php')
			->in($this->base_path);
	}
	
	public function factoryDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('factories')
			->in($this->base_path);
	}
	
	public function migrationDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('migrations')
			->in($this->base_path);
	}
	
	public function modelFileFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->depth('> 2')
			->path('src/Models')
			->name('*.php')
			->in($this->base_path);
	}
	
	public function routeFileFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->depth(2)
			->path('routes/')
			->name('*.php')
			->in($this->base_path);
	}
	
	public function viewDirectoryFinder(): FinderCollection
	{
		if ($this->basePathMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->depth('== 2')
			->path('resources/')
			->name('views')
			->in($this->base_path);
	}
	
	protected function basePathMissing(): bool
	{
		return false === $this->filesystem->isDirectory($this->base_path);
	}
}
