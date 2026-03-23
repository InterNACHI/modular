<?php

namespace InterNACHI\Modular\Plugins;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Plugins\Attributes\HandlesBoot;
use InterNACHI\Modular\Plugins\Attributes\HandlesRegister;
use InterNACHI\Modular\Support\FinderFactory;
use ReflectionAttribute;
use ReflectionClass;

abstract class Plugin
{
	public static function register(Closure $handler, Application $app): void
	{
		static::firstRegisterableAttribute()?->newInstance()->register(static::class, $handler, $app);
	}
	
	public static function boot(Closure $handler, Application $app): void
	{
		static::firstBootableAttribute()?->newInstance()->boot(static::class, $handler, $app);
	}
	
	/** @return ReflectionAttribute<HandlesRegister>|null */
	protected static function firstRegisterableAttribute(): ?ReflectionAttribute
	{
		$attributes = (new ReflectionClass(static::class))
			->getAttributes(HandlesRegister::class, ReflectionAttribute::IS_INSTANCEOF);
		
		return $attributes[0] ?? null;
	}
	
	/** @return ReflectionAttribute<HandlesBoot>|null */
	protected static function firstBootableAttribute(): ?ReflectionAttribute
	{
		$attributes = (new ReflectionClass(static::class))
			->getAttributes(HandlesBoot::class, ReflectionAttribute::IS_INSTANCEOF);
		
		return $attributes[0] ?? null;
	}
	
	abstract public function discover(FinderFactory $finders): iterable;
	
	abstract public function handle(Collection $data);
}
