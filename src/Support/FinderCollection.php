<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Traversable;

/**
 * @mixin \Illuminate\Support\LazyCollection
 * @mixin \Symfony\Component\Finder\Finder
 */
class FinderCollection implements Arrayable, IteratorAggregate
{
	use ForwardsCalls;
	
	protected const array PREFER_COLLECTION_METHODS = ['filter', 'each', 'map'];
	
	public static function forFiles(): self
	{
		return new static(Finder::create()->files());
	}
	
	public static function forDirectories(): self
	{
		return new static(Finder::create()->directories());
	}
	
	public function __construct(
		protected ?Finder $finder = null,
		protected ?LazyCollection $collection = null,
	) {
		if (! $this->finder && ! $this->collection) {
			$this->collection = new LazyCollection();
		}
	}
	
	public function inOrEmpty(string|array $dirs): static
	{
		try {
			return $this->in($dirs);
		} catch (DirectoryNotFoundException) {
			return new static();
		}
	}
	
	public function withModuleInfo(): static
	{
		return $this->map(fn(SplFileInfo $file) => new ModuleFileInfo($file));
	}
	
	public function getIterator(): Traversable
	{
		return $this->forwardCollection()->getIterator();
	}
	
	public function toArray(): array
	{
		return $this->forwardCollection()->toArray();
	}
	
	public function __call($name, $arguments)
	{
		$result = $this->forwardCallTo($this->forwardCallTargetForMethod($name), $name, $arguments);
		
		if ($result instanceof Finder) {
			return new static($result);
		}
		
		if ($result instanceof LazyCollection) {
			return new static($this->finder, $result);
		}
		
		return $result;
	}
	
	protected function forwardCallTargetForMethod(string $name): Finder|LazyCollection
	{
		if (is_callable([$this->finder, $name]) && ! in_array($name, static::PREFER_COLLECTION_METHODS)) {
			return $this->finder;
		}
		
		return $this->forwardCollection();
	}
	
	protected function forwardCollection(): LazyCollection
	{
		return $this->collection ??= new LazyCollection(function() {
			foreach ($this->finder as $key => $value) {
				yield $key => $value;
			}
		});
	}
}
