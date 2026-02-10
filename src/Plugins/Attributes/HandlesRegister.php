<?php

namespace InterNACHI\Modular\Plugins\Attributes;

use Closure;
use Illuminate\Foundation\Application;

interface HandlesRegister
{
	public function register(string $plugin, Closure $handler, Application $app);
}
