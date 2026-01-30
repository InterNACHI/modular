<?php

namespace InterNACHI\Modular\Support\Autodiscovery\Attributes;

use Attribute;
use Closure;
use Illuminate\Contracts\Container\Container;

#[Attribute(Attribute::TARGET_CLASS)]
class OnBoot implements HandlesAutodiscovery
{
	public function boot(string $plugin, Closure $handler, Container $app)
	{
		$handler($plugin);
	}
}
