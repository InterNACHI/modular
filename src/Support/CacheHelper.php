<?php

namespace InterNACHI\Modular\Support;

use BadMethodCallException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Throwable;

class CacheHelper
{
	protected const VERSION_KEY = '__cache_version';
	
	protected const CACHE_VERSION = 1;
	
	protected static $keys = [
		'modules',
		'commands',
		'legacy_factories',
		'migrations',
		'models',
		'blade_components',
		'routes',
		'view_directories',
	];
	
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
		// FIXME
		return false;
	}
	
	public function getFilename(): string
	{
		return $this->filename;
	}
	
	public function __call($name, $arguments)
	{
		if (in_array($name, static::$keys, true)) {
			if (count($arguments)) {
				$this->cache[$name] = $arguments[0];
			}
			
			return $this->cache[$name] ?? null;
		}
		
		throw new BadMethodCallException("There is no '{$name}' in the cache.");
	}
	
	protected function load(): array
	{
		try {
			$cache = include $this->filename;
			
			if (!is_array($cache)) {
				return [];
			}
			
			// Convert version-less cache to version "0"
			if (!isset($cache[static::VERSION_KEY])) {
				$cache = [
					static::VERSION_KEY => 0,
					static::MODULES_KEY => $cache,
				];
			}
		} catch (Throwable $exception) {
			return [];
		}
		
		return $cache;
	}
}
