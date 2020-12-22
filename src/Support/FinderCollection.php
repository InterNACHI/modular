<?php

namespace InterNACHI\Modular\Support;

use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;
use Traversable;

/**
 * @mixin \Illuminate\Support\LazyCollection
 * @mixin \Symfony\Component\Finder\Finder
 */
class FinderCollection implements IteratorAggregate, Arrayable
{
	use ForwardsCalls;
	
	protected static $prefer_collection_methods = ['filter', 'each', 'getIterator', 'toArray'];
	
	/**
	 * @var \Symfony\Component\Finder\Finder|Traversable
	 */
	protected $finder;
	
	/**
	 * @var \Illuminate\Support\LazyCollection
	 */
	protected $collection;
	
	public static function forFiles() : self
	{
		return (new static())->files();
	}
	
	public static function forDirectories() : self
	{
		return (new static())->directories();
	}
	
	public static function empty() : EmptyFinderCollection
	{
		return new EmptyFinderCollection();
	}
	
	public function __construct(Finder $finder = null)
	{
		$this->finder = $finder ?? new Finder();
		$this->collection = new LazyCollection(function() {
			foreach ($this->finder as $key => $value) {
				yield $key => $value;
			}
		});
	}
	
	public function getIterator()
	{
		return $this->__call('getIterator', []);
	}
	
	public function toArray()
	{
		return $this->__call('toArray', []);
	}
	
	public function __call($name, $arguments)
	{
		// Forward the call either to the Finder or the LazyCollection depending
		// on the method (always giving precedence to the Finder class unless otherwise configured)
		try {
			if (is_callable([$this->finder, $name]) && !in_array($name, static::$prefer_collection_methods)) {
				$result = $this->forwardCallTo($this->finder, $name, $arguments);
			} else {
				$result = $this->forwardCallTo($this->collection, $name, $arguments);
			}
		} catch (BadMethodCallException $exception) {
			// If we're chaining calls onto an intentionally empty collection, we'll just
			// silently handle bad method calls (i.e. calls to ->in() when there's not Finder
			// instance available). Otherwise, we'll re-throw them.
			if (is_array($this->finder)) {
				return $this;
			}
			
			throw $exception;
		}
		
		return $this->wrapForwardedResponse($result);
	}
	
	protected function wrapForwardedResponse($result)
	{
		// If we get a Finder object back, update our internal reference and chain
		if ($result instanceof Finder) {
			$this->finder = $result;
			return $this;
		}
		
		// If we get a Collection object back, update our internal reference and chain
		if ($result instanceof LazyCollection) {
			$this->collection = $result;
			// $this->collection->source = $this->finder;
			return $this;
		}
		
		// Otherwise, just return the new result (in the case of toBase() or sum()-type calls)
		return $result;
	}
}
