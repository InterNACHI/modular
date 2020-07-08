<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryHelperTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_discovers_commands() : void
	{
		$test_module = $this->makeModule('test-module');
		$test_module2 = $this->makeModule('test-module-two');
		
		$this->artisan(MakeCommand::class, [
			'name' => 'TestCommand',
			'--module' => 'test-module',
		]);
		
		$this->artisan(MakeCommand::class, [
			'name' => 'TestCommand',
			'--module' => 'test-module-two',
		]);
		
		$resolver = new AutoDiscoveryHelper(
			new ModuleRegistry($this->getBasePath().'/app-modules', ''),
			new Filesystem(),
			$this->getBasePath()
		);
		
		$resolved = [];
		
		$resolver->commandFileFinder()->each(function(SplFileInfo $command) use (&$resolved) {
			$resolved[] = $command->getPathname();
		});
		
		$this->assertContains($test_module->path('src/Console/Commands/TestCommand.php'), $resolved);
		$this->assertContains($test_module2->path('src/Console/Commands/TestCommand.php'), $resolved);
	}
}
