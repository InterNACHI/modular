<?php

namespace InterNACHI\Modular\Support\Autodiscovery;

use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\FinderFactory;

abstract class Plugin
{
	abstract public function discover(FinderFactory $finders): iterable;
	
	abstract public function handle(Collection $data);
}
