<?php

namespace InterNACHI\Modular\Support;

class AutoDiscoveryHelper
{
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $module_registry;
	
	/**
	 * @var string
	 */
	protected $modules_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\CacheHelper
	 */
	protected $cache;
	
	public function __construct(ModuleRegistry $module_registry, CacheHelper $cache)
	{
		$this->module_registry = $module_registry;
		$this->modules_path = rtrim($module_registry->getModulesPath(), DIRECTORY_SEPARATOR);
		$this->cache = $cache;
	}
	
	public function modulesFinder() : FinderCollection
	{
		return $this->fileFinder()
			->depth('== 1')
			->name('composer.json');
	}
	
	public function commandFileFinder() : FinderCollection
	{
		return $this->fileFinder('*/src/Console/Commands/')
			->name('*.php');
	}
	
	public function factoryDirectoryFinder() : FinderCollection
	{
		return $this->directoryFinder('*/database/')
			->depth(0)
			->name('factories');
	}
	
	public function migrationDirectoryFinder(): FinderCollection
	{
		return $this->directoryFinder('*/database/')
			->depth(0)
			->name('migrations');
	}
	
	public function modelFileFinder(): FinderCollection
	{
		return $this->fileFinder('*/src/Models/')
			->name('*.php');
	}
	
	public function bladeComponentFileFinder() : FinderCollection
	{
		return $this->fileFinder('*/src/View/Components/')
			->name('*.php');
	}
	
	public function routeFileFinder(): FinderCollection
	{
		return $this->fileFinder('*/routes/')
			->depth(0)
			->name('*.php');
	}
	
	public function viewDirectoryFinder() : FinderCollection
	{
		return $this->directoryFinder('*/resources/')
			->depth(0)
			->name('views');
	}
	
	protected function fileFinder(string $in = '') : FinderCollection
	{
		if ($this->modulesPathIsMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forFiles()
			->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
	}
	
	protected function directoryFinder(string $in = '') : FinderCollection
	{
		if ($this->modulesPathIsMissing()) {
			return FinderCollection::empty();
		}
		
		return FinderCollection::forDirectories()
			->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
	}
	
	protected function modulesPathIsMissing() : bool
	{
		return false === is_dir($this->modules_path);
	}
}
