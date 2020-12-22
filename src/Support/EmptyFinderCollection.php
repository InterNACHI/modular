<?php

namespace InterNACHI\Modular\Support;

use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;
use Traversable;

class EmptyFinderCollection extends FinderCollection
{
	public function __construct()
	{
		$this->finder = [];
		$this->collection = new LazyCollection($this->finder);
	}
	
	public function __call($name, $arguments)
	{
		if (method_exists($this->collection, $name)) {
			$result = $this->forwardCallTo($this->collection, $name, $arguments);
		} else {
			$result = $this->collection;
		}
		
		return $this->wrapForwardedResponse($result);
	}
}
