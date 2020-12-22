<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryHelper implements Arrayable
{
	/**
	 * @var string
	 */
	protected $modules_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\CacheHelper|null
	 */
	protected $cache;
	
	/**
	 * @var \Closure[] 
	 */
	protected $loaders;
	
	public function __construct(string $modules_path, CacheHelper $cache = null)
	{
		$this->modules_path = rtrim($modules_path, DIRECTORY_SEPARATOR);
		$this->cache = $cache;
		
		$this->loaders = $this->getLoaders();
	}
	
	public function toArray()
	{
		return Collection::make($this->loaders)
			->map(function(Closure $loader) {
				return $loader();
			})
			->toArray();
	}
	
	public function modules() : ?Collection
	{
		return $this->load('modules');
	}
	
	public function commands() : ?Collection
	{
		return $this->load('commands');
	}
	
	public function legacyFactoryPaths() : ?Collection
	{
		return $this->load('legacy_factories');
	}
	
	public function migrations() : ?Collection
	{
		return $this->load('migrations');
	}
	
	public function models(): ?Collection
	{
		return $this->load('models');
	}
	
	public function bladeComponents() : ?Collection
	{
		return $this->load('blade_components');
	}
	
	public function routes(): ?Collection
	{
		return $this->load('routes');
	}
	
	public function viewDirectories() : ?Collection
	{
		return $this->load('view_directories');
	}
	
	protected function load(string $name) : ?Collection
	{
		$result = $this->cache
			? $this->cache->get($name)
			: null;
		
		if (!$result) {
			$result = $this->loaders[$name]();
		}
		
		if ($result instanceof EmptyFinderCollection) {
			return null;
		}
		
		return new Collection($result);
	}
	
	protected function fileFinder(string $in = '') : FinderCollection
	{
		try {
			return FinderCollection::forFiles()
				->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}
	
	protected function directoryFinder(string $in = '') : FinderCollection
	{
		try {
			return FinderCollection::forDirectories()
				->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
		} catch (DirectoryNotFoundException $exception) {
			return FinderCollection::empty();
		}
	}
	
	/**
	 * These loaders are meant to build data that can be manipulated by
	 * modular or cached in production. They should return a Collection
	 * of data that contains only PHP primitive values (arrays, strings, etc).
	 * 
	 * @return \Closure[]
	 */
	protected function getLoaders(): array
	{
		return [
			'blade_components' => function() {
				return $this->fileFinder('*/src/View/Components/')
					->name('*.php')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
			'commands' => function() {
				return $this->fileFinder('*/src/Console/Commands/')
					->name('*.php')
					->map(function(SplFileInfo $file) {
						return $file->getPathname();
					});
			},
			'legacy_factories' => function() {
				return $this->directoryFinder('*/database')
					->depth(0)
					->name('factories')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
			'migrations' => function() {
				return $this->directoryFinder('*/database/')
					->depth(0)
					->name('migrations')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
			'models' => function() {
				return $this->fileFinder('*/src/Models/')
					->name('*.php')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
			'modules' => function() {
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
							})
							->all();
						
						return [$name => compact('name', 'base_path', 'namespaces')];
					});
			},
			'routes' => function() {
				return $this->fileFinder('*/routes/')
					->depth(0)
					->name('*.php')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
			'view_directories' => function() {
				return $this->directoryFinder('*/resources/')
					->depth(0)
					->name('views')
					->map(function(SplFileInfo $path) {
						return $path->getPathname();
					});
			},
		];
	}
}
