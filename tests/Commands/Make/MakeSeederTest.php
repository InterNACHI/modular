<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeSeeder;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeSeederTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_seeder_in_the_module_when_module_option_is_set(): void
	{
		$command = MakeSeeder::class;
		$arguments = ['name' => 'TestSeeder'];
		$expected_path = version_compare($this->app->version(), '8.0.0', '>=')
			? 'database/seeders/TestSeeder.php'
			: 'database/seeds/TestSeeder.php';
		$expected_substrings = [
			'use Illuminate\Database\Seeder',
			'class TestSeeder extends Seeder',
		];
		
		if (version_compare($this->app->version(), '8.0.0', '>=')) {
			$expected_substrings[] = 'namespace Modules\TestModule\Database\Seeders;';
		}
		
		$this->filesystem()->deleteDirectory($this->getBasePath().$this->normalizeDirectorySeparators('database/seeds'));
		$this->filesystem()->deleteDirectory($this->getModulePath('test-module', 'database/seeds'));
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_seeder_in_the_app_when_module_option_is_missing(): void
	{
		$command = MakeSeeder::class;
		$arguments = ['name' => 'TestSeeder'];
		$expected_path = version_compare($this->app->version(), '8.0.0', '>=')
			? 'database/seeders/TestSeeder.php'
			: 'database/seeds/TestSeeder.php';
		$expected_substrings = [
			'use Illuminate\Database\Seeder',
			'class TestSeeder extends Seeder',
		];
		
		if (version_compare($this->app->version(), '8.0.0', '>=')) {
			$expected_substrings[] = 'namespace Database\Seeders;';
		}
		
		$this->filesystem()->deleteDirectory($this->getBasePath().$this->normalizeDirectorySeparators('database/seeds'));
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
