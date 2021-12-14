<?php

namespace InterNACHI\Modular\Support;

use RuntimeException;
use Throwable;

class CacheHelper
{
	protected const VERSION_KEY = '__cache_version';
	
	protected const CACHE_VERSION = 1;
	
	protected string $filename;
	
	protected array $cache = [];
	
	public function __construct(string $filename)
	{
		$this->filename = $filename;
		$this->cache[static::VERSION_KEY] = static::CACHE_VERSION;
		
		if (is_readable($this->filename)) {
			$this->load();
		}
	}
	
	public function write(): bool
	{
		$export = collect($this->cache)->toArray();
		
		$contents = '<?php return '.var_export($export, true).';'.PHP_EOL;
		
		return file_put_contents($this->filename, $contents);
	}
	
	public function delete(): bool
	{
		if (!file_exists($this->filename)) {
			return true;
		}
		
		return unlink($this->filename);
	}
	
	public function has(string $name): bool
	{
		return array_key_exists($name, $this->cache);
	}
	
	public function get(string $name)
	{
		return $this->cache[$name] ?? null;
	}
	
	public function set(string $name, array $value): self
	{
		$this->cache[$name] = $value;
		
		return $this;
	}
	
	public function forget(string $name): self
	{
		unset($this->cache[$name]);
		
		return $this;
	}
	
	public function clear(): self
	{
		$this->cache = [];
		
		return $this;
	}
	
	public function reload(): self
	{
		if (!is_readable($this->filename)) {
			return $this;
		}
		
		try {
			$cache = include $this->filename;
			
			if (!is_array($cache)) {
				throw new RuntimeException('Module cache is not a valid array of data.');
			}
			
			$cache = $this->migrate($cache);
			
			if ($cache[static::VERSION_KEY] !== static::CACHE_VERSION) {
				throw new RuntimeException("Unrecognized modular cache version: {$cache[static::VERSION_KEY]}");
			}
			
			$this->cache = $cache;
		} catch (Throwable $exception) {
			@unlink($this->filename);
			$this->cache = [];
			
			throw $exception;
		}
		
		return $this;
	}
	
	protected function load()
	{
		try {
			$this->reload();
		} catch (Throwable $exception) {
			//
		}
	}
	
	protected function migrate(array $cache): array
	{
		// Migrate from version-less array to v1
		if (!isset($cache[static::VERSION_KEY])) {
			$cache = [
				static::VERSION_KEY => 1,
				'modules' => $cache,
			];
		}
		
		return $cache;
	}
}
