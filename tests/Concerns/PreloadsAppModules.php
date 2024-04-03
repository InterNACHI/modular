<?php

namespace InterNACHI\Modular\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;

trait PreloadsAppModules
{
	protected static $autoloader_registered = false;
	
	/** @before */
	public function prepareTestModule(): void
	{
		$src = __DIR__.'/../testbench-core/app-modules';
		$dest = static::applicationBasePath().'/app-modules';
		
		$fs = new Filesystem();
		$fs->deleteDirectory($dest);
		$fs->copyDirectory($src, $dest);
	}
	
	/** @before */
	public function prepareModuleAutoloader(): void
	{
		if (! static::$autoloader_registered) {
			spl_autoload_register(function($fqcn) {
				if (str_starts_with($fqcn, 'Modules\\TestModule\\')) {
					$path = str_replace(
						['Modules\\TestModule\\', '\\'],
						['', DIRECTORY_SEPARATOR],
						$fqcn
					);
					$path = static::applicationBasePath().'/app-modules/test-module/src/'.$path.'.php';
					include_once $path;
				}
			});
		}
		
		static::$autoloader_registered = true;
	}
}
