<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class CacheHelper
{
	protected const VERSION_KEY = '__cache_version';
	
	protected const CACHE_VERSION = 1;
	
	/**
	 * @var string
	 */
	protected $filename;
	
	/**
	 * @var array 
	 */
	protected $cache = [];
	
	public function __construct(string $filename)
	{
		$this->filename = $filename;
		$this->cache[static::VERSION_KEY] = static::CACHE_VERSION;
		
		if (is_readable($this->filename)) {
			$this->cache = $this->load();
		}
	}
	
	public function write(): bool
	{
		$export = Collection::make($this->cache)->toArray();
		
		$contents = '<?php return '.var_export($export, true).';'.PHP_EOL;
		
		return file_put_contents($this->filename, $contents);
	}
	
	public function getFilename() : string
	{
		return $this->filename;
	}
	
	public function get(string $name)
	{
		return $this->cache[$name] ?? null;
	}
	
	public function set(string $name, array $value) : self
	{
		$this->cache[$name] = $value;
		
		return $this;
	}
	
	public function forget(string $name) : self
	{
		unset($this->cache[$name]);
		
		return $this;
	}
	
	protected function load() : array
	{
		try {
			$cache = include $this->filename;
		} catch (Throwable $exception) {
			return $this->cache;
		}
		
		if (!is_array($cache)) {
			return $this->cache;
		}
		
		$cache = $this->migrate($cache);
		
		if ($cache[static::VERSION_KEY] !== static::CACHE_VERSION) {
			throw new RuntimeException("Unrecognized modular cache version: {$cache[static::VERSION_KEY]}");
		}
		
		return $cache;
	}
	
	protected function migrate(array $cache) : array
	{
		// Migrate from version-less array to v1
		if (!isset($cache[static::VERSION_KEY])) {
			$cache = [
				static::VERSION_KEY => static::CACHE_VERSION,
				'modules' => $cache,
			];
		}
		
		return $cache;
	}
}
