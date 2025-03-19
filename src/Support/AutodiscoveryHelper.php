<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use Livewire\LivewireManager;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	public function __construct(
		protected FinderFactory $finders,
		protected Filesystem $fs,
		protected string $cache_path,
	) {
	}
	
	public function writeCache(Container $app): void
	{
		$helpers = [
			$this->modules(...),
			$this->routes(...),
			$this->views(...),
			$this->blade(...),
			$this->translations(...),
			$this->migrations(...),
			$this->commands(...),
			$this->policies(...),
			$this->livewire(...),
		];
		
		foreach ($helpers as $helper) {
			try {
				$app->call($helper);
			} catch (BindingResolutionException) {
				
			}
		}
		
		$cache = Collection::make($this->data)->toArray();
		$php = '<?php return '.var_export($cache, true).';'.PHP_EOL;
		
		$this->fs->ensureDirectoryExists($this->fs->dirname($this->cache_path));
		
		if (! $this->fs->put($this->cache_path, $php)) {
			throw new RuntimeException('Unable to write cache file.');
		}
		
		try {
			require $this->cache_path;
		} catch (Throwable $e) {
			$this->fs->delete($this->cache_path);
			throw new RuntimeException('Attempted to write invalid cache file.', $e->getCode(), $e);
		}
	}
	
	public function clearCache(): void
	{
		if ($this->fs->exists($this->cache_path)) {
			$this->fs->delete($this->cache_path);
		}
	}
	
	/** @return Collection<string, \InterNACHI\Modular\Support\ModuleConfig> */
	public function modules(bool $reload = false): Collection
	{
		if ($reload) {
			unset($this->data['modules']);
		}
		
		$data = $this->withCache(
			key: 'modules',
			default: fn() => $this->finders
				->moduleComposerFileFinder()
				->values()
				->mapWithKeys(function(SplFileInfo $file) {
					$composer_config = json_decode($file->getContents(), true, 16, JSON_THROW_ON_ERROR);
					$base_path = rtrim(str_replace('\\', '/', $file->getPath()), '/');
					$name = basename($base_path);
					
					return [
						$name => [
							'name' => $name,
							'base_path' => $base_path,
							'namespaces' => Collection::make($composer_config['autoload']['psr-4'] ?? [])
								->mapWithKeys(fn($src, $namespace) => ["{$base_path}/{$src}" => $namespace])
								->all(),
						],
					];
				}),
		);
		
		return Collection::make($data)
			->map(fn(array $d) => new ModuleConfig($d['name'], $d['base_path'], new Collection($d['namespaces'])));
	}
	
	public function routes(): void
	{
		$this->withCache(
			key: 'route_files',
			default: fn() => $this->finders
				->routeFileFinder()
				->values()
				->map(fn(SplFileInfo $file) => $file->getRealPath()),
			each: fn(string $filename) => require $filename
		);
	}
	
	public function views(ViewFactory $factory): void
	{
		$this->withCache(
			key: 'view_namespaces',
			default: fn() => $this->finders
				->viewDirectoryFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $dir) => [
					'namespace' => $dir->module()->name,
					'path' => $dir->getRealPath(),
				]),
			each: fn(array $row) => $factory->addNamespace($row['namespace'], $row['path']),
		);
	}
	
	public function blade(BladeCompiler $blade): void
	{
		// Handle individual Blade components (old syntax: `<x-module-* />`)
		$this->withCache(
			key: 'blade_component_files',
			default: fn() => $this->finders
				->bladeComponentFileFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $component) => [
					'prefix' => $component->module()->name,
					'fqcn' => $component->fullyQualifiedClassName(),
				]),
			each: fn(array $row) => $blade->component($row['fqcn'], null, $row['prefix']),
		);
		
		// Handle Blade component namespaces (new syntax: `<x-module::* />`)
		$this->withCache(
			key: 'blade_component_dirs',
			default: fn() => $this->finders
				->bladeComponentDirectoryFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $component) => [
					'prefix' => $component->module()->name,
					'namespace' => $component->module()->qualify('View\\Components'),
				]),
			each: fn(array $row) => $blade->componentNamespace($row['namespace'], $row['prefix']),
		);
	}
	
	public function translations(Translator $translator): void
	{
		$this->withCache(
			key: 'translation_files',
			default: fn() => $this->finders
				->langDirectoryFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $dir) => [
					'namespace' => $dir->module()->name,
					'path' => $dir->getRealPath(),
				]),
			each: function(array $row) use ($translator) {
				$translator->addNamespace($row['namespace'], $row['path']);
				$translator->addJsonPath($row['path']);
			},
		);
	}
	
	public function migrations(Migrator $migrator): void
	{
		$this->withCache(
			key: 'migration_files',
			default: fn() => $this->finders
				->migrationDirectoryFinder()
				->values()
				->map(fn(SplFileInfo $file) => $file->getRealPath()),
			each: fn(string $path) => $migrator->path($path),
		);
	}
	
	public function commands(Artisan $artisan): void
	{
		$this->withCache(
			key: 'command_files',
			default: fn() => $this->finders
				->commandFileFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $file) => $file->fullyQualifiedClassName())
				->filter($this->isInstantiableCommand(...)),
			each: fn(string $fqcn) => $artisan->resolve($fqcn),
		);
	}
	
	public function policies(Gate $gate): void
	{
		$this->withCache(
			key: 'model_policy_files',
			default: fn() => $this->finders
				->modelFileFinder()
				->withModuleInfo()
				->values()
				->map(function(ModuleFileInfo $file) use ($gate) {
					$fqcn = $file->fullyQualifiedClassName();
					$namespace = rtrim($file->module()->namespaces->first(), '\\');
					
					$candidates = [
						$namespace.'\\Policies\\'.Str::after($fqcn, 'Models\\').'Policy', // Policies/Foo/BarPolicy
						$namespace.'\\Policies\\'.Str::afterLast($fqcn, '\\').'Policy',   // Policies/BarPolicy
					];
					
					foreach ($candidates as $candidate) {
						if (class_exists($candidate)) {
							return [
								'fqcn' => $fqcn,
								'policy' => $candidate,
							];
						}
					}
					
					return null;
				})
				->filter(),
			each: fn(array $row) => $gate->policy($row['fqcn'], $row['policy']),
		);
	}
	
	public function livewire(LivewireManager $livewire): void
	{
		$this->withCache(
			key: 'livewire_component_files',
			default: fn() => $this->finders
				->livewireComponentFileFinder()
				->withModuleInfo()
				->values()
				->map(fn(ModuleFileInfo $file) => [
					'name' => sprintf(
						'%s::%s',
						$file->module()->name,
						Str::of($file->getRelativePath())
							->explode('/')
							->filter()
							->push($file->getBasename('.php'))
							->map([Str::class, 'kebab'])
							->implode('.')
					),
					'fqcn' => $file->fullyQualifiedClassName(),
				]),
			each: fn(array $row) => $livewire->component($row['name'], $row['fqcn']),
		);
	}
	
	protected function withCache(
		string $key,
		Closure $default,
		?Closure $each = null,
	): iterable {
		$this->data ??= $this->readData();
		$this->data[$key] ??= value($default);
		
		return $each
			? Collection::make($this->data[$key])->each($each)
			: $this->data[$key];
	}
	
	protected function readData(): array
	{
		try {
			return $this->fs->exists($this->cache_path)
				? require $this->cache_path
				: [];
		} catch (Throwable) {
			return [];
		}
	}
	
	protected function isInstantiableCommand($command): bool
	{
		return is_subclass_of($command, Command::class)
			&& ! (new ReflectionClass($command))->isAbstract();
	}
}
