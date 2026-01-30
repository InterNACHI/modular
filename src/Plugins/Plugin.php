<?php

namespace InterNACHI\Modular\Plugins;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Attributes\HandlesAutodiscovery;
use InterNACHI\Modular\Support\FinderFactory;
use ReflectionAttribute;
use ReflectionClass;

abstract class Plugin
{
	abstract public function discover(FinderFactory $finders): iterable;
	
	abstract public function handle(Collection $data);
	
	public static function boot(Closure $handler, Application $app): void
	{
		/** @var ReflectionAttribute<HandlesAutodiscovery>[] $attributes */
		$attributes = (new ReflectionClass(static::class))->getAttributes(HandlesAutodiscovery::class, ReflectionAttribute::IS_INSTANCEOF);
		
		if (count($attributes)) {
			$attributes[0]->newInstance()->boot(static::class, $handler, $app);
		}
	}
}
