<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryHelper
{
	/**
	 * @var string
	 */
	protected $modules_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\CacheHelper|null
	 */
	protected $cache;
	
	public function __construct(string $modules_path, CacheHelper $cache = null)
	{
		$this->modules_path = rtrim($modules_path, DIRECTORY_SEPARATOR);
		$this->cache = $cache;
	}
	
	public function modules() : Collection
	{
		return $this->load('modules', function() {
			return $this->fileFinder()
				->depth('== 1')
				->name('composer.json')
				->mapWithKeys(function(SplFileInfo $composer_file) {
					$composer_config = json_decode($composer_file->getContents(), true, 16, JSON_THROW_ON_ERROR);
					
					$base_path = rtrim($composer_file->getPath(), DIRECTORY_SEPARATOR);
					
					$name = basename($base_path);
					
					$namespaces = Collection::make($composer_config['autoload']['psr-4'] ?? [])
						->mapWithKeys(function($src, $namespace) use ($base_path) {
							$src = str_replace('/', DIRECTORY_SEPARATOR, $src);
							$path = $base_path.DIRECTORY_SEPARATOR.$src;
							return [$path => $namespace];
						});
					
					return [$name => compact('name', 'base_path', 'namespaces')];
				});
		});
	}
	
	public function commands() : Collection
	{
		return $this->load('commands', function() {
			return $this->fileFinder('*/src/Console/Commands/')
				->name('*.php')
				->map(function(SplFileInfo $file) {
					return $file->getPathname();
				});
		});
	}
	
	public function legacyFactoryPaths() : Collection
	{
		return $this->load('legacy_factories', function() {
			return $this->directoryFinder('*/database')
				->depth(0)
				->name('factories')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function migrations() : Collection
	{
		return $this->load('migrations', function() {
			return $this->directoryFinder('*/database/')
				->depth(0)
				->name('migrations')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function models(): Collection
	{
		return $this->load('models', function() {
			return $this->fileFinder('*/src/Models/')
				->name('*.php')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
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
	
	protected function load(string $name, Closure $loader) : Collection
	{
		$cached = $this->cache
			? call_user_func([$this->cache, $name])
			: null;
		
		return new Collection($cached ?? $loader());
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
