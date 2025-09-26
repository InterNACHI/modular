<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use InterNACHI\Modular\Support\Autodiscovery\Plugin;
use Livewire\LivewireManager;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	protected array $plugins = [];
	
	public function __construct(
		protected FinderFactory $finders,
		protected Filesystem $fs,
		protected Container $app,
		protected string $cache_path,
	) {
	}
	
	public function writeCache(Container $app): void
	{
		foreach ($this->plugins as $plugin) {
			$this->discover($plugin);
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
	
	/** @param class-string<Plugin> $plugin */
	public function register(string $plugin): static
	{
		$this->plugins[$plugin] ??= null;
		
		return $this;
	}
	
	/** @param class-string<Plugin> $plugin */
	public function boot(string $plugin): Closure
	{
		return fn(...$args) => $this->plugin($plugin)->boot(...$args);
	}
	
	/** @param class-string<Plugin> $name */
	public function discover(string $name): Collection
	{
		$this->data ??= $this->readData();
		$this->data[$name] ??= $this->plugin($name)->discover($this->finders);
		
		return collect($this->data[$name]);
	}
	
	/** @param class-string<Plugin> $name */
	public function handle(string $name): mixed
	{
		return $this->plugin($name)
			->handle($this->discover($name));
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
	
	public function events(Dispatcher $events, bool $autodiscover = true): void
	{
		$this->withCache(
			key: 'events',
			default: fn() => $autodiscover
				? $this->finders
					->listenerDirectoryFinder()
					->withModuleInfo()
					->reduce(function(array $discovered, ModuleFileInfo $file) {
						return array_merge_recursive(
							$discovered,
							DiscoverEvents::within($file->getPathname(), $file->module()->path('src'))
						);
					}, [])
				: [],
			each: function(array $listeners, string $event) use ($events) {
				foreach (array_unique($listeners, SORT_REGULAR) as $listener) {
					$events->listen($event, $listener);
				}
			},
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
	
	/**
	 * @template TPlugin of Plugin
	 * @param class-string<TPlugin> $plugin
	 * @return TPlugin
	 */
	public function plugin(string $plugin): Plugin
	{
		return $this->plugins[$plugin] ??= $this->app->make($plugin);
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
