<?php

namespace InterNACHI\Modular\Plugins\Attributes;

use Closure;
use Illuminate\Foundation\Application;

interface HandlesAutodiscovery
{
	public function boot(string $plugin, Closure $handler, Application $app);
}
