<?php

namespace InterNACHI\Modular\Plugins\Attributes;

use Attribute;
use Closure;
use Illuminate\Foundation\Application;

#[Attribute(Attribute::TARGET_CLASS)]
class OnBoot implements HandlesAutodiscovery
{
	public function boot(string $plugin, Closure $handler, Application $app)
	{
		$handler($plugin);
	}
}
