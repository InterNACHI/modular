<?php

namespace InterNACHI\Modular\Support;

use InterNACHI\Modular\Support\Facades\Modules;
use SplFileInfo;

class DiscoverEvents extends \Illuminate\Foundation\Events\DiscoverEvents
{
	protected static function classFromFile(SplFileInfo $file, $basePath)
	{
		if ($module = Modules::moduleForPath($file->getRealPath())) {
			return $module->pathToFullyQualifiedClassName($file->getPathname());
		}
		
		return parent::classFromFile($file, $basePath);
	}
}
