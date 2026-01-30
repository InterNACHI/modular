<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Exceptions\CannotWriteCacheException;
use Throwable;

class Cache
{
	public function __construct(
		protected Filesystem $fs,
		protected string $cache_path,
	) {
	}
	
	public function read(): array
	{
		try {
			return $this->fs->exists($this->cache_path) ? require $this->cache_path : [];
		} catch (Throwable) {
			return [];
		}
	}
	
	public function write(array $data): bool
	{
		$cache = Collection::make($data)->toArray();
		$php = '<?php return '.var_export($cache, true).';'.PHP_EOL;
		
		$this->fs->ensureDirectoryExists($this->fs->dirname($this->cache_path));
		
		if (! $this->fs->put($this->cache_path, $php)) {
			throw new CannotWriteCacheException($this->cache_path);
		}
		
		try {
			require $this->cache_path;
		} catch (Throwable $e) {
			$this->fs->delete($this->cache_path);
			throw new CannotWriteCacheException($this->cache_path, $e);
		}
		
		return true;
	}
	
	public function clear(): void
	{
		if ($this->fs->exists($this->cache_path)) {
			$this->fs->delete($this->cache_path);
		}
	}
}
