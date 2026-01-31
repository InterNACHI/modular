<?php

namespace InterNACHI\Modular\Tests\Plugins;

use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class MigratorPluginTest extends TestCase
{
	use PreloadsAppModules;

	public function test_migration_paths_are_registered(): void
	{
		$migrator = $this->app['migrator'];
		$paths = $migrator->paths();

		$moduleMigrationPath = null;
		foreach ($paths as $path) {
			if (str_contains($path, 'test-module/database/migrations')) {
				$moduleMigrationPath = $path;
				break;
			}
		}

		$this->assertNotNull($moduleMigrationPath, 'Module migration path should be registered');
	}

	public function test_module_migrations_are_discoverable(): void
	{
		$migrator = $this->app['migrator'];
		$files = $migrator->getMigrationFiles($migrator->paths());

		$moduleMigrations = array_filter(
			$files,
			fn($path) => str_contains($path, 'test-module')
		);

		$this->assertNotEmpty($moduleMigrations);
	}
}
