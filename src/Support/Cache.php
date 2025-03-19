<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;

class Cache
{
	protected ?array $data = null;
	
	public function __construct(
		protected string $path,
		protected Filesystem $filesystem,
	) {
	}
	
	public function get(string $key, Closure $callback): Enumerable
	{
		return Collection::make($this->data($key, $callback));
	}
	
	public function save(): bool
	{
		$cache_contents = '<?php return '.var_export($this->data, true).';'.PHP_EOL;
		
		return $this->filesystem->put($this->path, $cache_contents);
	}
	
	protected function data(?string $key = null, mixed $default = null): array
	{
		if (null === $this->data && $this->filesystem->exists($this->path)) {
			$this->data = require $this->path;
		}
		
		return Arr::get($this->data, $key, $default);
	}
}
