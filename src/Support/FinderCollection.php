<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;

/**
 * @mixin \Illuminate\Support\LazyCollection
 * @mixin \Symfony\Component\Finder\Finder
 */
class FinderCollection implements IteratorAggregate
{
	use ForwardsCalls;
	
	protected static array $prefer_collection_methods = ['filter', 'each'];
	
	protected Finder $finder;
	
	protected LazyCollection $collection;
	
	public static function forFiles(): self
	{
		return (new static())->files();
	}
	
	public static function forDirectories(): self
	{
		return (new static())->directories();
	}
	
	public function __construct(Finder $finder = null)
	{
		$this->finder = $finder ?? new Finder();
		$this->collection = new LazyCollection();
	}
	
	public function __call($name, $arguments)
	{
		// Forward the call either to the Finder or the LazyCollection depending
		// on the method (always giving precedence to the Finder class unless otherwise configured)
		if (!in_array($name, static::$prefer_collection_methods) && is_callable([$this->finder, $name])) {
			$result = $this->forwardCallTo($this->finder, $name, $arguments);
		} else {
			$this->collection->source = $this->finder;
			$result = $this->forwardCallTo($this->collection, $name, $arguments);
		}
		
		// If we get a Finder object back, update our internal reference and chain
		if ($result instanceof Finder) {
			$this->finder = $result;
			return $this;
		}
		
		// If we get a Collection object back, update our internal reference and chain
		if ($result instanceof LazyCollection) {
			$this->collection = $result;
			return $this;
		}
		
		// Otherwise, just return the new result (in the case of toBase() or sum()-type calls)
		return $result;
	}
	
	public function getIterator()
	{
		return $this->collection->getIterator();
	}
}
