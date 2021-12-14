<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeCommand;
use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Console\Commands\Make\MakeLivewire;
use InterNACHI\Modular\Console\Commands\Make\MakeModel;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\Cache;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use Livewire\LivewireServiceProvider;

class AutoDiscoveryHelperTest extends TestCase
{
	use WritesToAppFilesystem;
	
	protected $module1;
	
	protected $module2;
	
	protected $helper;
	
	protected function setUp(): void
	{
		parent::setUp();
		
		$this->module1 = $this->makeModule('test-module');
		$this->module2 = $this->makeModule('test-module-two');
		$this->helper = new AutoDiscoveryHelper($this->getBasePath().'/app-modules', []);
	}
	
	protected function tearDown(): void
	{
		$cache_path = $this->getBasePath().'/bootstrap/cache/modules.php';
		if ($this->filesystem()->exists($cache_path)) {
			$this->filesystem()->delete($cache_path);
		}
		
		parent::tearDown();
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
		
		$this->helper->getCommands()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
		});
		
		$this->assertContains($this->module1->path('src/Console/Commands/TestCommand.php'), $resolved);
		$this->assertContains($this->module2->path('src/Console/Commands/TestCommand.php'), $resolved);
	}
	
	public function test_it_finds_factory_directories(): void
	{
		$resolved = [];
		
		$this->helper->getLegacyFactories()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
		});
		
		$this->assertContains($this->module1->path('database/factories'), $resolved);
		$this->assertContains($this->module2->path('database/factories'), $resolved);
	}
	
	public function test_it_finds_migration_directories(): void
	{
		$resolved = [];
		
		$this->helper->getMigrations()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
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
		
		$this->helper->getModels()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
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
		
		$resolved = [];
		
		$this->helper->getBladeComponents()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
		});
		
		$this->assertContains($this->module1->path('src/View/Components/TestComponent.php'), $resolved);
		$this->assertContains($this->module2->path('src/View/Components/TestComponent.php'), $resolved);
	}
	
	public function test_it_finds_routes(): void
	{
		$resolved = [];
		
		$this->helper->getRoutes()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
		});
		
		$this->assertContains($this->module1->path("routes/{$this->module1->name}-routes.php"), $resolved);
		$this->assertContains($this->module2->path("routes/{$this->module2->name}-routes.php"), $resolved);
	}
	
	public function test_it_finds_view_directories(): void
	{
		$resolved = [];
		
		$this->helper->getViewDirectories()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
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
		
		$this->helper->getLangDirectories()->each(function($pathname) use (&$resolved) {
			$resolved[] = $pathname;
		});
		
		$this->assertContains($this->module1->path('resources/lang'), $resolved);
		$this->assertContains($this->module2->path('resources/lang'), $resolved);
	}
	
	public function test_it_finds_livewire_component(): void
	{
		$this->artisan(MakeLivewire::class, [
			'name' => 'TestComponent',
			'--module' => $this->module1->name,
		]);
		
		$this->artisan(MakeLivewire::class, [
			'name' => 'TestComponent',
			'--module' => $this->module2->name,
		]);
		
		$resolved = [];
		
		$this->helper->getLivewireComponentFiles()->each(function(array $component) use (&$resolved) {
			$resolved[] = $component[0];
		});
		
		$this->assertContains($this->module1->path('src/Http/Livewire/TestComponent.php'), $resolved);
		$this->assertContains($this->module2->path('src/Http/Livewire/TestComponent.php'), $resolved);
	}
	
	protected function getPackageProviders($app)
	{
		$providers = parent::getPackageProviders($app);
		
		if (class_exists(LivewireServiceProvider::class)) {
			$providers[] = LivewireServiceProvider::class;
		}
		
		return $providers;
	}
}
