<?php

namespace InterNACHI\Modular\Support;

use BadMethodCallException;
use Illuminate\Support\Str;
use UnexpectedValueException;

trait FormatsModuleNames
{
	protected function formatModuleName(string $name): string
	{
		$case = config('app-modules.name_case', 'kebab');
		
		try {
			return Str::$case($name);
		} catch (BadMethodCallException $e) {
			throw new UnexpectedValueException("Unknown module name case: '{$case}'");
		}
	}
}
