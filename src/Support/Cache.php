<?php

namespace InterNACHI\Modular\Support;

use RuntimeException;
use Throwable;

class Cache
{
	protected const VERSION_KEY = '__cache_version';
	
	protected const CACHE_VERSION = 1;
	
	public ?Throwable $error = null;
	
	protected string $filename;
	
	protected array $cache = [];
	
	public function __construct(string $filename)
	{
		$this->filename = $filename;
		$this->cache[static::VERSION_KEY] = static::CACHE_VERSION;
		
		$this->load();
	}
	
	public function toArray(): array
	{
		return $this->cache;
	}
	
	public function write(array $data): bool
	{
		$this->cache = $data;
		
		$export = collect($this->cache)->toArray();
		
		$contents = '<?php return '.var_export($export, true).';'.PHP_EOL;
		
		return file_put_contents($this->filename, $contents);
	}
	
	public function delete(): bool
	{
		$this->cache = [];
		
		if (!file_exists($this->filename)) {
			return true;
		}
		
		return unlink($this->filename);
	}
	
	public function load(): bool
	{
		if (!is_readable($this->filename)) {
			return true;
		}
		
		try {
			$this->cache = include $this->filename;
			
			if (!is_array($this->cache)) {
				throw new RuntimeException('Module cache is not a valid array of data.');
			}
			
			$this->migrate();
			
			if ($this->cache[static::VERSION_KEY] !== static::CACHE_VERSION) {
				throw new RuntimeException("Unrecognized modular cache version: {$this->cache[static::VERSION_KEY]}");
			}
			
			return true;
		} catch (Throwable $exception) {
			@unlink($this->filename);
			$this->cache = [];
			$this->error = $exception;
			return false;
		}
	}
	
	protected function migrate(): void
	{
		// Migrate from version-less array to v1
		if (!isset($this->cache[static::VERSION_KEY])) {
			$this->cache = [
				static::VERSION_KEY => 1,
				'modules' => $this->cache,
			];
		}
	}
}
