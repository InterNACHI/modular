<?php

namespace InterNACHI\Modular\Support\Autodiscovery\Attributes;

use Closure;
use Illuminate\Foundation\Application;

interface HandlesAutodiscovery
{
	public function boot(string $plugin, Closure $handler, Application $app);
}
