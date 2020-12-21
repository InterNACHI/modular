<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\CacheHelper;
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
		$this->helper = new AutoDiscoveryHelper($this->getBasePath().'/app-modules');
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
		
		$resolved = $this->helper->commands();
		
		$this->assertContains($this->module1->path('src/Console/Commands/TestCommand.php'), $resolved);
		$this->assertContains($this->module2->path('src/Console/Commands/TestCommand.php'), $resolved);
	}
	
	public function test_it_finds_factory_directories() : void
	{
		$resolved = $this->helper->legacyFactoryPaths();
		
		$this->assertContains($this->module1->path('database/factories'), $resolved);
		$this->assertContains($this->module2->path('database/factories'), $resolved);
	}
	
	public function test_it_finds_migration_directories() : void
	{
		$resolved = $this->helper->migrations();
		
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
		
		$resolved = $this->helper->models();
		
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
		
		$resolved = $this->helper->bladeComponents();
		
		$this->assertContains($this->module1->path('src/View/Components/TestComponent.php'), $resolved);
		$this->assertContains($this->module2->path('src/View/Components/TestComponent.php'), $resolved);
	}
	
	public function test_it_finds_routes() : void
	{
		$resolved = $this->helper->routes();
		
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
}
