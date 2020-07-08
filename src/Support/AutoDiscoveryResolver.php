<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryResolver
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
	
	public function discoverCommands(Closure $callback): void
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forFiles()
			->depth('> 3')
			->path('src/Console/Commands')
			->name('*.php')
			->in($this->base_path)
			->map(function(SplFileInfo $file) {
				if (!$module = $this->module_registry->moduleForPath($file->getPath())) {
					throw new RuntimeException("Unable to determine module for '{$file->getPath()}'");
				}
				
				return $this->pathToFullyQualifiedClassName($file->getPathname(), $module);
			})
			->filter(function($class_name) {
				return $this->isInstantiableCommand($class_name);
			})
			->each($callback);
	}
	
	public function discoverFactories(Closure $callback): void
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('factories')
			->in($this->base_path)
			->each($callback);
	}
	
	public function discoverMigrations(Closure $callback): void
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('migrations')
			->in($this->base_path)
			->each($callback);
	}
	
	public function discoverPolicies(Closure $callback): void
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forFiles()
			->depth('> 2')
			->path('src/Models')
			->name('*.php')
			->in($this->base_path)
			->map(function(SplFileInfo $file) {
				if (!$module = $this->module_registry->moduleForPath($file->getPath())) {
					throw new RuntimeException("Unable to determine module for '{$file->getPath()}'");
				}
				
				$fully_qualified_model = $this->pathToFullyQualifiedClassName($file->getPathname(), $module);
				
				// First, check for a policy that maps to the full namespace of the model
				// i.e. Models/Foo/Bar -> Policies/Foo/BarPolicy
				$namespaced_model = Str::after($fully_qualified_model, 'Models\\');
				$namespaced_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$namespaced_model.'Policy';
				if (class_exists($namespaced_policy)) {
					return [$fully_qualified_model, $namespaced_policy];
				}
				
				// If that doesn't match, try the simple mapping as well
				// i.e. Models/Foo/Bar -> Policies/BarPolicy
				if (false !== strpos($namespaced_model, '\\')) {
					$simple_model = Str::afterLast($fully_qualified_model, '\\');
					$simple_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$simple_model.'Policy';
					
					if (class_exists($simple_policy)) {
						return [$fully_qualified_model, $simple_policy];
					}
				}
				
				return null;
			})
			->reject(function($result) {
				return null === $result;
			})
			->eachSpread($callback);
	}
	
	public function discoverRoutes(Closure $callback): void 
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forFiles()
			->depth(2)
			->path('routes/')
			->name('*.php')
			->in($this->base_path)
			->each($callback);
	}
	
	public function discoverViewPaths(Closure $callback): void 
	{
		if ($this->basePathMissing()) {
			return;
		}
		
		FinderCollection::forDirectories()
			->depth(0)
			->in($this->base_path)
			->each($callback);
	}
	
	protected function basePathMissing(): bool
	{
		return false === $this->filesystem->isDirectory($this->base_path);
	}
	
	protected function pathToFullyQualifiedClassName($path, ModuleConfig $module_config): string
	{
		foreach ($module_config->namespaces as $namespace_path => $namespace) {
			if (0 === strpos($path, $namespace_path)) {
				$relative_path = Str::after($path, $namespace_path);
				return $namespace.$this->formatPathAsNamespace($relative_path);
			}
		}
		
		throw new RuntimeException("Unable to infer qualified class name for '{$path}'");
	}
	
	protected function getModulesBasePath() : string
	{
		if (null === $this->modules_path) {
			$directory_name = $this->app->make('config')->get('app-modules.modules_directory', 'app-modules');
			$this->modules_path = $this->app->basePath($directory_name);
		}
		
		return $this->modules_path;
	}
	
	protected function modulesBasePathExists() : bool
	{
		return $this->app->make(Filesystem::class)
			->isDirectory($this->getModulesBasePath());
	}
	
	protected function formatPathAsNamespace(string $path) : string
	{
		$path = trim($path, DIRECTORY_SEPARATOR);
		
		$replacements = [
			'/' => '\\',
			'.php' => '',
		];
		
		return str_replace(
			array_keys($replacements),
			array_values($replacements),
			$path
		);
	}
	
	protected function isInstantiableCommand($command): bool
	{
		return is_subclass_of($command, Command::class)
			&& !(new ReflectionClass($command))->isAbstract();
	}
}
