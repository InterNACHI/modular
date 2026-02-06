<?php

namespace InterNACHI\Modular\Plugins\Attributes;

use Attribute;
use Closure;
use Illuminate\Foundation\Application;

#[Attribute(Attribute::TARGET_CLASS)]
class AfterResolving implements HandlesBoot
{
	public function __construct(
		public string $abstract,
		public string $parameter,
	) {
	}
	
	public function boot(string $plugin, Closure $handler, Application $app)
	{
		$app->afterResolving($this->abstract, fn($resolved) => $handler($plugin, [$this->parameter => $resolved]));
		
		if ($app->resolved($this->abstract)) {
			$handler($plugin);
		}
	}
}
