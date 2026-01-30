<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\Autodiscovery\Attributes\HandlesAutodiscovery;
use InterNACHI\Modular\Support\Autodiscovery\Plugin;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Throwable;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	protected array $plugins = [];
	
	protected array $handled = [];
	
	public function __construct(
		protected FinderFactory $finders,
		protected Filesystem $fs,
		protected Container $app,
		protected string $cache_path,
	) {
	}
	
	public function writeCache(): void
	{
		foreach (array_keys($this->plugins) as $plugin) {
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
		
		$this->handled = [];
		$this->data = null;
	}
	
	/** @param class-string<Plugin> $plugin */
	public function register(string $plugin): static
	{
		$this->plugins[$plugin] ??= null;
		
		return $this;
	}
	
	public function bootPlugins(): void
	{
		foreach ($this->plugins as $class => $_) {
			$attributes = (new ReflectionClass($class))->getAttributes(HandlesAutodiscovery::class, ReflectionAttribute::IS_INSTANCEOF);
			if (count($attributes)) {
				$attributes[0]->newInstance()->boot($class, $this->handle(...), $this->app);
			}
		}
	}
	
	/** @param class-string<Plugin> $name */
	public function discover(string $name): Collection
	{
		$this->data ??= $this->readCacheIfExists();
		$this->data[$name] ??= $this->plugin($name)->discover($this->finders);
		
		return collect($this->data[$name]);
	}
	
	/** @param class-string<Plugin> $name */
	public function handle(string $name, array $parameters = []): mixed
	{
		return $this->handled[$name] ??= $this->plugin($name, $parameters)->handle($this->discover($name));
	}
	
	public function handleIf(string $name, bool $condition): mixed
	{
		if ($condition) {
			return $this->handle($name);
		}
		
		return null;
	}
	
	/**
	 * @template TPlugin of Plugin
	 * @param class-string<TPlugin> $plugin
	 * @return TPlugin
	 */
	public function plugin(string $plugin, array $parameters = []): Plugin
	{
		return $this->plugins[$plugin] ??= $this->app->make($plugin, $parameters);
	}
	
	protected function readCacheIfExists(): array
	{
		try {
			return $this->fs->exists($this->cache_path)
				? require $this->cache_path
				: [];
		} catch (Throwable) {
			return [];
		}
	}
}
