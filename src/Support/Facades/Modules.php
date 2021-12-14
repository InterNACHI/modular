<?php

namespace InterNACHI\Modular\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;

/**
 * @method static ModuleConfig|null module(string $name)
 * @method static ModuleConfig|null moduleForPath(string $path)
 * @method static Collection modules()
 * @method static ModuleRegistry clear()
 *
 * @see \InterNACHI\Modular\Support\ModuleRegistry
 */
class Modules extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return ModuleRegistry::class;
	}
}
