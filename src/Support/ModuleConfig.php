<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ModuleConfig implements Arrayable
{
	public string $name;
	
	public string $base_path;
	
	public Collection $namespaces;
	
	public static function fromArray(array $data): self
	{
		if (! isset($data['name'], $data['base_path'], $data['namespaces'])) {
			throw new InvalidArgumentException('Module data array must contain name, base_path, and namespaces.');
		}
		
		return new static(
			$data['name'],
			$data['base_path'],
			new Collection($data['namespaces'])
		);
	}
	
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
		return $this->namespaces->first();
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
