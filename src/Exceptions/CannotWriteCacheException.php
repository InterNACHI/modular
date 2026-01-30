<?php

namespace InterNACHI\Modular\Exceptions;

use Throwable;

class CannotWriteCacheException extends Exception
{
	public function __construct(string $path, ?Throwable $previous = null)
	{
		parent::__construct("Unable to write to '{$path}'", 0, $previous);
	}
}
