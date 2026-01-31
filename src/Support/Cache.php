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
		protected string $path,
	) {
	}
	
	public function read(): array
	{
		try {
			return $this->fs->exists($this->path) ? require $this->path : [];
		} catch (Throwable) {
			return [];
		}
	}
	
	public function write(array $data): bool
	{
		$cache = Collection::make($data)->toArray();
		$php = '<?php return '.var_export($cache, true).';'.PHP_EOL;
		
		$this->fs->ensureDirectoryExists($this->fs->dirname($this->path));
		
		if (! $this->fs->put($this->path, $php)) {
			throw new CannotWriteCacheException($this->path);
		}
		
		try {
			require $this->path;
		} catch (Throwable $e) {
			$this->fs->delete($this->path);
			throw new CannotWriteCacheException($this->path, $e);
		}
		
		return true;
	}
	
	public function clear(): void
	{
		if ($this->fs->exists($this->path)) {
			$this->fs->delete($this->path);
		}
	}
}
