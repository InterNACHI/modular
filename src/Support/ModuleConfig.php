<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

class ModuleConfig implements Arrayable
{
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string
	 */
	public $base_path;
	
	/**
	 * @var Collection
	 */
	public $namespaces;
	
	public function __construct($name, $base_path, Collection $namespaces = null)
	{
		$this->name = $name;
		$this->base_path = $base_path;
		$this->namespaces = $namespaces ?? new Collection();
	}
	
	public function path(string $to = ''): string
	{
		return rtrim($this->base_path.DIRECTORY_SEPARATOR.$to, DIRECTORY_SEPARATOR);
	}
	
	public function namespace(): string
	{
		return $this->namespaces->first(function($namespace, $path) {
			return false !== stripos($path, 'src'.DIRECTORY_SEPARATOR);
		});
	}
	
	public function qualify(string $class_name): string
	{
		return $this->namespace().ltrim($class_name, '\\');
	}
	
	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'base_path' => $this->base_path,
			'namespaces' => $this->namespaces->toArray(),
		];
	}
}
