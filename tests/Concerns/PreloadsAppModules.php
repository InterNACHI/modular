<?php

namespace InterNACHI\Modular\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Before;

trait PreloadsAppModules
{
	protected static $autoloader_registered = false;

	#[Before]
	public function prepareTestModule(): void
	{
		$src = $this->appModulesFixturePath();
		$dest = static::applicationBasePath().'/app-modules';

		$fs = new Filesystem();
		$fs->deleteDirectory($dest);
		$fs->copyDirectory($src, $dest);
	}

	#[Before]
	public function prepareModuleAutoloader(): void
	{
		if (static::$autoloader_registered) {
			return;
		}

		$namespaces = $this->moduleNamespaceMap();
		$basePath = static::applicationBasePath();

		spl_autoload_register(function($fqcn) use ($namespaces, $basePath) {
			foreach ($namespaces as $namespace => $module) {
				if (! str_starts_with($fqcn, $namespace)) {
					continue;
				}
				$relative = str_replace(
					[$namespace, '\\'],
					['', DIRECTORY_SEPARATOR],
					$fqcn
				);
				$path = $basePath.'/app-modules/'.$module.'/src/'.$relative.'.php';
				if (file_exists($path)) {
					include_once $path;
				}
				return;
			}
		});

		static::$autoloader_registered = true;
	}

	protected function appModulesFixturePath(): string
	{
		return __DIR__.'/../testbench-core/app-modules';
	}

	protected function moduleNamespaceMap(): array
	{
		return ['Modules\\TestModule\\' => 'test-module'];
	}
}
