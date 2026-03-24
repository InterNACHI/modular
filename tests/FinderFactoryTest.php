<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Console\Commands\Make\MakeListener;
use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use Symfony\Component\Finder\SplFileInfo;

class FinderFactoryTest extends TestCase
{
	use WritesToAppFilesystem;
	
	protected ModuleConfig $module1;
	
	protected ModuleConfig $module2;
	
	protected FinderFactory $factory;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		$this->module1 = $this->makeModule('test-module');
		$this->module2 = $this->makeModule('test-module-two');
		$this->factory = new FinderFactory($this->getApplicationBasePath().'/app-modules');
	}
	
	public function test_it_finds_commands(): void
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
		
		$this->factory->commandFileFinder()->each(function(SplFileInfo $command) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $command->getPathname());
		});
		
		$this->assertContains($this->module1->path('src/Console/Commands/TestCommand.php'), $resolved);
		$this->assertContains($this->module2->path('src/Console/Commands/TestCommand.php'), $resolved);
	}
	
	public function test_it_finds_factory_directories(): void
	{
		$resolved = [];
		
		$this->factory->factoryDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $directory->getPathname());
		});
		
		$this->assertContains($this->module1->path('database/factories'), $resolved);
		$this->assertContains($this->module2->path('database/factories'), $resolved);
	}
	
	public function test_it_finds_migration_directories(): void
	{
		$resolved = [];
		
		$this->factory->migrationDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $directory->getPathname());
		});
		
		$this->assertContains($this->module1->path('database/migrations'), $resolved);
		$this->assertContains($this->module2->path('database/migrations'), $resolved);
	}
	
	public function test_it_finds_models(): void
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
		
		$this->factory->modelFileFinder()->each(function(SplFileInfo $file) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $file->getPathname());
		});
		
		$this->assertContains($this->module1->path('src/Models/TestModel.php'), $resolved);
		$this->assertContains($this->module2->path('src/Models/TestModel.php'), $resolved);
	}
	
	public function test_it_finds_blade_components(): void
	{
		$this->artisan(MakeComponent::class, [
			'name' => 'TestComponent',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeComponent::class, [
			'name' => 'TestComponent',
			'--module' => $this->module2->name,
		]);
		
		$resolved_directories = [];
		$resolved_files = [];
		
		$this->factory->bladeComponentDirectoryFinder()->each(function(SplFileInfo $file) use (&$resolved_directories) {
			$resolved_directories[] = str_replace('\\', '/', $file->getPathname());
		});
		
		$this->factory->bladeComponentFileFinder()->each(function(SplFileInfo $file) use (&$resolved_files) {
			$resolved_files[] = str_replace('\\', '/', $file->getPathname());
		});
		
		$this->assertContains($this->module1->path('src/View/Components'), $resolved_directories);
		$this->assertContains($this->module2->path('src/View/Components'), $resolved_directories);
		
		$this->assertContains($this->module1->path('src/View/Components/TestComponent.php'), $resolved_files);
		$this->assertContains($this->module2->path('src/View/Components/TestComponent.php'), $resolved_files);
	}
	
	public function test_it_finds_routes(): void
	{
		$resolved = [];
		
		$this->factory->routeFileFinder()->each(function(SplFileInfo $file) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $file->getPathname());
		});
		
		$this->assertContains($this->module1->path("routes/{$this->module1->name}-routes.php"), $resolved);
		$this->assertContains($this->module2->path("routes/{$this->module2->name}-routes.php"), $resolved);
	}
	
	public function test_it_finds_view_directories(): void
	{
		$resolved = [];
		
		$this->factory->viewDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $directory->getPathname());
		});
		
		$this->assertContains($this->module1->path('resources/views'), $resolved);
		$this->assertContains($this->module2->path('resources/views'), $resolved);
	}
	
	public function test_it_finds_lang_directories(): void
	{
		// These paths don't exist by default
		$fs = new Filesystem();
		$fs->makeDirectory($this->module1->path('resources/lang'));
		$fs->makeDirectory($this->module2->path('resources/lang'));
		
		$resolved = [];
		
		$this->factory->langDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $directory->getPathname());
		});
		
		$this->assertContains($this->module1->path('resources/lang'), $resolved);
		$this->assertContains($this->module2->path('resources/lang'), $resolved);
	}
	
	public function test_it_finds_lang_directories_when_they_are_in_the_module_root_directory(): void
	{
		// These paths don't exist by default
		$fs = new Filesystem();
		$fs->makeDirectory($this->module1->path('lang'));
		$fs->makeDirectory($this->module2->path('lang'));
		
		$resolved = [];
		
		$this->helper->langDirectoryFinder()->each(function(SplFileInfo $directory) use (&$resolved) {
			$resolved[] = str_replace('\\', '/', $directory->getPathname());
		});
		
		$this->assertContains($this->module1->path('lang'), $resolved);
		$this->assertContains($this->module2->path('lang'), $resolved);
	}
	
	public function test_it_finds_event_listeners(): void
	{
		$this->artisan(MakeListener::class, [
			'name' => 'TestListener',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeListener::class, [
			'name' => 'TestListener',
			'--module' => $this->module2->name,
		]);
		
		$resolved = $this->factory->listenerDirectoryFinder()
			->map(fn(SplFileInfo $directory) => str_replace('\\', '/', $directory->getPathname()))
			->all();
		
		$this->assertContains($this->module1->path('src/Listeners'), $resolved);
		$this->assertContains($this->module2->path('src/Listeners'), $resolved);
	}
}
