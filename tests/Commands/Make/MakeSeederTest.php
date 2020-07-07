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
	
	public function test_it_scaffolds_a_seeder_in_the_module_when_module_option_is_set() : void
	{
		$command = MakeSeeder::class;
		$arguments = ['name' => 'TestSeeder'];
		$expected_path = 'database/seeds/TestSeeder.php';
		$expected_substrings = [
			'use Illuminate\Database\Seeder',
			'class TestSeeder extends Seeder',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_seeder_in_the_app_when_module_option_is_missing() : void
	{
		$command = MakeSeeder::class;
		$arguments = ['name' => 'TestSeeder'];
		$expected_path = 'database/seeds/TestSeeder.php';
		$expected_substrings = [
			'use Illuminate\Database\Seeder',
			'class TestSeeder extends Seeder',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
