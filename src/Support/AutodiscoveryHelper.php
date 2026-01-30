<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\Autodiscovery\Plugin;
use InterNACHI\Modular\Support\Autodiscovery\PluginRegistry;
use RuntimeException;
use Throwable;

class AutodiscoveryHelper
{
	protected ?array $data = null;
	
	protected array $handled = [];
	
	public function __construct(
		protected PluginRegistry $registry,
		protected FinderFactory $finders,
		protected Filesystem $fs,
		protected Container $app,
		protected string $cache_path,
	) {
	}
	
	public function writeCache(): void
	{
		foreach ($this->registry->all() as $plugin) {
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
	
	public function bootPlugins(Application $app): void
	{
		foreach ($this->registry->all() as $class) {
			$class::boot($this->handle(...), $app);
		}
	}
	
	/** @param class-string<Plugin> $name */
	public function discover(string $name): Collection
	{
		$this->data ??= $this->readCacheIfExists();
		$this->data[$name] ??= $this->registry->plugin($name)->discover($this->finders);
		
		return collect($this->data[$name]);
	}
	
	/** @param class-string<Plugin> $name */
	public function handle(string $name, array $parameters = []): mixed
	{
		return $this->handled[$name] ??= $this->registry->plugin($name, $parameters)->handle($this->discover($name));
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
