<?php

namespace InterNACHI\Modular\Support\Autodiscovery\Attributes;

use Closure;
use Illuminate\Contracts\Container\Container;

interface HandlesAutodiscovery
{
	public function boot(string $plugin, Closure $handler, Container $app);
}
