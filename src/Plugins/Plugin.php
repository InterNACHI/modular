<?php

namespace InterNACHI\Modular\Plugins;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Attributes\HandlesBoot;
use InterNACHI\Modular\Support\FinderFactory;
use ReflectionAttribute;
use ReflectionClass;

abstract class Plugin
{
	abstract public function discover(FinderFactory $finders): iterable;
	
	abstract public function handle(Collection $data);
	
	public static function boot(Closure $handler, Application $app): void
	{
		/** @var ReflectionAttribute<HandlesBoot>[] $attributes */
		$attributes = (new ReflectionClass(static::class))->getAttributes(HandlesBoot::class, ReflectionAttribute::IS_INSTANCEOF);
		
		if (count($attributes)) {
			$attributes[0]->newInstance()->boot(static::class, $handler, $app);
		}
	}
}
