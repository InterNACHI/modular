<?php

namespace InterNACHI\Modular\Tests\Plugins;

use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class MigratorPluginTest extends TestCase
{
	use PreloadsAppModules;
	
	public function test_migration_paths_are_registered(): void
	{
		$migrator = $this->app->make('migrator');
		$paths = $migrator->paths();
		
		$this->assertTrue(
			collect($paths)->contains(fn($path) => str_contains($path, 'test-module/database/migrations')),
			'Module migration path should be registered: '.json_encode($paths)
		);
	}
	
	public function test_module_migrations_are_discoverable(): void
	{
		$migrator = $this->app->make('migrator');
		$files = $migrator->getMigrationFiles($migrator->paths());
		
		$this->assertArrayHasKey('2024_04_03_133130_set_up_test_module', $files);
		$this->assertStringContainsString(
			'app-modules/test-module/database/migrations',
			str_replace('\\', '/', $files['2024_04_03_133130_set_up_test_module'])
		);
	}
}
