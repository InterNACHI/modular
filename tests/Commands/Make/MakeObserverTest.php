<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeObserver;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeObserverTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;
	
	public function test_it_scaffolds_a_observer_in_the_module_when_module_option_is_set() : void
	{
		$command = MakeObserver::class;
		$arguments = ['name' => 'TestObserver'];
		$expected_path = 'src/Observers/TestObserver.php';
		$expected_substrings = [
			'namespace Modules\TestModule\Observers',
			'class TestObserver',
		];
		
		$this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
	
	public function test_it_scaffolds_a_observer_in_the_app_when_module_option_is_missing() : void
	{
		$command = MakeObserver::class;
		$arguments = ['name' => 'TestObserver'];
		$expected_path = 'app/Observers/TestObserver.php';
		$expected_substrings = [
			'namespace App\Observers',
			'class TestObserver',
		];
		
		$this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);
	}
}
