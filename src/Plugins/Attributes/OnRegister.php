<?php

namespace InterNACHI\Modular\Plugins\Attributes;

use Attribute;
use Closure;
use Illuminate\Foundation\Application;

#[Attribute(Attribute::TARGET_CLASS)]
class OnRegister implements HandlesRegister
{
	public function register(string $plugin, Closure $handler, Application $app)
	{
		$handler($plugin);
	}
}
