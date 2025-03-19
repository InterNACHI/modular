<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use Livewire\LivewireManager;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	public function __construct(
		protected FinderFactory $finders,
		protected Filesystem $filesystem,
		protected string $cache_path,
	) {
	}
	
	public function routes(): void
	{
		$this->withCache(
			key: 'route_files',
			default: fn() => $this->finders
				->routeFileFinder()
				->map(fn(SplFileInfo $file) => $file->getRealPath()),
			each: fn(string $filename) => require $filename
		);
	}
	
	public function views(ViewFactory $factory): void
	{
		$this->withCache(
			key: 'view_namespaces',
			default: $this->finders
				->viewDirectoryFinder()
				->withModuleInfo()
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
			default: $this->finders
				->bladeComponentFileFinder()
				->withModuleInfo()
				->map(fn(ModuleFileInfo $component) => [
					'prefix' => $component->module()->name,
					'fqcn' => $component->fullyQualifiedClassName(),
				]),
			each: fn(array $row) => $blade->component($row['fqcn'], null, $row['prefix']),
		);
		
		// Handle Blade component namespaces (new syntax: `<x-module::* />`)
		$this->withCache(
			key: 'blade_component_dirs',
			default: $this->finders
				->bladeComponentDirectoryFinder()
				->withModuleInfo()
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
			key: 'blade_component_files',
			default: $this->finders
				->langDirectoryFinder()
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
			default: $this->finders
				->migrationDirectoryFinder()
				->map(fn(SplFileInfo $file) => $file->getRealPath()),
			each: fn(string $path) => $migrator->path($path),
		);
	}
	
	public function commands(Artisan $artisan): void
	{
		$this->withCache(
			key: 'command_files',
			default: $this->finders
				->commandFileFinder()
				->withModuleInfo()
				->map(fn(ModuleFileInfo $file) => $file->fullyQualifiedClassName())
				->filter($this->isInstantiableCommand(...)),
			each: fn(string $fqcn) => $artisan->resolve($fqcn),
		);
	}
	
	public function policies(Gate $gate): void
	{
		$this->withCache(
			key: 'model_policy_files',
			default: $this->finders
				->modelFileFinder()
				->withModuleInfo()
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
			default: $this->finders
				->livewireComponentFileFinder()
				->withModuleInfo()
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
	): Enumerable|array {
		$this->data ??= $this->readData();
		$this->data[$key] ??= value($default);
		
		return $each
			? collect($this->data[$key])->each($each)
			: $this->data[$key];
	}
	
	protected function readData(): array
	{
		try {
			return $this->filesystem->exists($this->cache_path)
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
