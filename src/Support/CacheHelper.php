<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Throwable;

class CacheHelper
{
	protected const VERSION_KEY = '__cache_version';
	
	protected const CACHE_VERSION = 1;
	
	protected const MODULES_KEY = 'modules';
	
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
	
	public function modules(array $modules = null) : ?array
	{
		if ($modules) {
			$this->cache[static::MODULES_KEY] = $modules;
		}
		
		return $this->cache[static::MODULES_KEY] ?? null;
	}
	
	public function commandFiles(): Collection
	{
		// FIXME
	}
	
	public function factoryDirectories(): Collection
	{
		// FIXME
	}
	
	public function migrationDirectories(): Collection
	{
		// FIXME
	}
	
	public function modelFiles(): Collection
	{
		// FIXME
	}
	
	public function bladeComponentFiles(): Collection
	{
		// FIXME
	}
	
	public function routeFiles(): Collection
	{
		// FIXME
	}
	
	public function viewDirectories(): Collection
	{
		// FIXME
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
