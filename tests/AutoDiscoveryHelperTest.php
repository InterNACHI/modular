<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use Symfony\Component\Finder\SplFileInfo;

class AutoDiscoveryHelperTest extends TestCase
{
	use WritesToAppFilesystem;
	
	protected $module1;
	
	protected $module2;
	
	protected $helper;
	
	protected function setUp() : void
	{
		parent::setUp();
		
		$this->module1 = $this->makeModule('test-module');
		$this->module2 = $this->makeModule('test-module-two');
		$this->helper = new AutoDiscoveryHelper(
			new ModuleRegistry($this->getBasePath().'/app-modules', ''),
			new Filesystem()
		);
	}
	
	public function test_it_finds_commands() : void
	{
		$this->artisan(MakeCommand::class, [
			'name' => 'TestCommand',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeCommand::class, [
			'name' => 'TestCommand',
			'--module' => $this->module2->name,
		]);
		
		$resolved = [];
		
		$this->helper->commandFileFinder()->each(function(SplFileInfo $command) use (&$resolved) {
			$resolved[] = $command->getPathname();
		});
		
		$this->assertContains($this->module1->path('src/Console/Commands/TestCommand.php'), $resolved);
		$this->assertContains($this->module2->path('src/Console/Commands/TestCommand.php'), $resolved);
	}
	
	public function test_it_finds_factory_directories() : void
	{
		$resolved = [];
		
		$this->helper->factoryDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = $directory->getPathname();
		});
		
		$this->assertContains($this->module1->path('database/factories'), $resolved);
		$this->assertContains($this->module2->path('database/factories'), $resolved);
	}
	
	public function test_it_finds_migration_directories() : void
	{
		$resolved = [];
		
		$this->helper->migrationDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = $directory->getPathname();
		});
		
		$this->assertContains($this->module1->path('database/migrations'), $resolved);
		$this->assertContains($this->module2->path('database/migrations'), $resolved);
	}
	
	public function test_it_finds_models() : void
	{
		$this->artisan(MakeModel::class, [
			'name' => 'TestModel',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeModel::class, [
			'name' => 'TestModel',
			'--module' => $this->module2->name,
		]);
		
		$resolved = [];
		
		$this->helper->modelFileFinder()->each(function(SplFileInfo $file) use (&$resolved) {
			$resolved[] = $file->getPathname();
		});
		
		$this->assertContains($this->module1->path('src/Models/TestModel.php'), $resolved);
		$this->assertContains($this->module2->path('src/Models/TestModel.php'), $resolved);
	}
	
	public function test_it_finds_blade_components() : void
	{
		$this->artisan(MakeComponent::class, [
			'name' => 'TestComponent',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeComponent::class, [
			'name' => 'TestComponent',
			'--module' => $this->module2->name,
		]);
		
		$resolved = [];
		
		$this->helper->bladeComponentFileFinder()->each(function(SplFileInfo $file) use (&$resolved) {
			$resolved[] = $file->getPathname();
		});
		
		$this->assertContains($this->module1->path('src/View/Components/TestComponent.php'), $resolved);
		$this->assertContains($this->module2->path('src/View/Components/TestComponent.php'), $resolved);
	}
	
	public function test_it_finds_routes() : void
	{
		$resolved = [];
		
		$this->helper->routeFileFinder()->each(function(SplFileInfo $file) use (&$resolved) {
			$resolved[] = $file->getPathname();
		});
		
		$this->assertContains($this->module1->path("routes/{$this->module1->name}-routes.php"), $resolved);
		$this->assertContains($this->module2->path("routes/{$this->module2->name}-routes.php"), $resolved);
	}
	
	public function test_it_finds_view_directories() : void
	{
		$resolved = [];
		
		$this->helper->viewDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = $directory->getPathname();
		});
		
		$this->assertContains($this->module1->path('resources/views'), $resolved);
		$this->assertContains($this->module2->path('resources/views'), $resolved);
	}
	
	public function test_it_finds_lang_directories() : void
	{
		// These paths don't exist by default
		$fs = new Filesystem();
		$fs->makeDirectory($this->module1->path('resources/lang'));
		$fs->makeDirectory($this->module2->path('resources/lang'));
		
		$resolved = [];
		
		$this->helper->langDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = $directory->getPathname();
		});
		
		$this->assertContains($this->module1->path('resources/lang'), $resolved);
		$this->assertContains($this->module2->path('resources/lang'), $resolved);
	}
}
