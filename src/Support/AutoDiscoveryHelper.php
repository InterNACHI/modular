<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryHelper
{
	protected ModuleRegistry $module_registry;
	
	protected CacheHelper $cache;
	
	protected string $modules_path;
	
	public function __construct(ModuleRegistry $module_registry, CacheHelper $cache)
	{
		$this->module_registry = $module_registry;
		$this->modules_path = $module_registry->getModulesPath();
		$this->cache = $cache;
	}
	
	// ------------------------------------
	
	public function getModules(): ?Collection
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
						})
						->all();
					
					return [$name => compact('name', 'base_path', 'namespaces')];
				});
		});
	}
	
	public function getBladeComponents(): ?Collection
	{
		return $this->load('blade_components', function() {
			return $this->fileFinder('*/src/View/Components/')
				->name('*.php')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function getCommands(): Collection
	{
		return $this->load('commands', function() {
			return $this->fileFinder('*/src/Console/Commands/')
				->name('*.php')
				->map(function(SplFileInfo $file) {
					return $file->getPathname();
				});
		});
	}
	
	public function getLegacyFactories(): ?Collection
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
	
	public function getMigrations(): ?Collection
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
	
	public function getModels(): ?Collection
	{
		return $this->load('models', function() {
			return $this->fileFinder('*/src/Models/')
				->name('*.php')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function getRoutes(): ?Collection
	{
		return $this->load('routes', function() {
			return $this->fileFinder('*/routes/')
				->depth(0)
				->name('*.php')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function getViewDirectories(): ?Collection
	{
		return $this->load('view_directories', function() {
			return $this->directoryFinder('*/resources/')
				->depth(0)
				->name('views')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function getLangDirectories(): ?Collection
	{
		return $this->load('lang_directories', function() {
			return $this->directoryFinder('*/resources/')
				->depth(0)
				->name('lang')
				->map(function(SplFileInfo $path) {
					return $path->getPathname();
				});
		});
	}
	
	public function getLivewireComponentFiles(): ?Collection
	{
		return $this->load('livewire_components', function() {
			return $this->fileFinder('*/src/Http/Livewire')
				->name('*.php')
				->map(function(SplFileInfo $component) {
					$component_name = Str::of($component->getRelativePath())
						->explode('/')
						->filter()
						->push($component->getBasename('.php'))
						->map([Str::class, 'kebab'])
						->implode('.');
					
					return [$component->getPathname(), $component_name];
				});
		});
	}
	
	protected function load(string $name, Closure $loader): Collection
	{
		if (!$this->cache->has($name)) {
			try {
				$this->cache->set($name, collect($loader())->toArray());
			} catch (DirectoryNotFoundException $exception) {
				$this->cache->set($name, []);
			}
		}
		
		return collect($this->cache->get($name));
	}
	
	protected function fileFinder(string $in = ''): FinderCollection
	{
		return FinderCollection::forFiles()
			->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
	}
	
	protected function directoryFinder(string $in = ''): FinderCollection
	{
		return FinderCollection::forDirectories()
			->in($this->modules_path.DIRECTORY_SEPARATOR.$in);
	}
}
