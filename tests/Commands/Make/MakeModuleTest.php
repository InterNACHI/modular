<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\TestCase;

class MakeModuleTest extends TestCase
{
	protected $base_path;
	
	protected function setUp() : void
	{
		parent::setUp();
		
		Modules::reload();
	}
	
	public function test_it_scaffolds_a_new_module() : void
	{
		$module_name = 'test-module';
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		]);
		
		$fs = new Filesystem();
		$module_path = $this->getBasePath().DIRECTORY_SEPARATOR.'app-modules'.DIRECTORY_SEPARATOR.$module_name;
		
		$this->assertTrue($fs->isDirectory($module_path));
		$this->assertTrue($fs->isDirectory($module_path.DIRECTORY_SEPARATOR.'database'));
		$this->assertTrue($fs->isDirectory($module_path.DIRECTORY_SEPARATOR.'resources'));
		$this->assertTrue($fs->isDirectory($module_path.DIRECTORY_SEPARATOR.'routes'));
		$this->assertTrue($fs->isDirectory($module_path.DIRECTORY_SEPARATOR.'src'));
		$this->assertTrue($fs->isDirectory($module_path.DIRECTORY_SEPARATOR.'tests'));
		
		$composer_file = $module_path.DIRECTORY_SEPARATOR.'composer.json';
		$this->assertTrue($fs->isFile($composer_file));
		
		$composer_contents = json_decode($fs->get($composer_file), true);
		
		$this->assertEquals("modules/{$module_name}", $composer_contents['name']);
		$this->assertContains('database/factories', $composer_contents['autoload']['classmap']);
		$this->assertContains('database/seeds', $composer_contents['autoload']['classmap']);
		$this->assertEquals('src/', $composer_contents['autoload']['psr-4']['Modules\\TestModule\\']);
		$this->assertEquals('tests/', $composer_contents['autoload-dev']['psr-4']['Modules\\TestModule\\Tests\\']);
		$this->assertContains('Modules\\TestModule\\Providers\\TestModuleServiceProvider', $composer_contents['extra']['laravel']['providers']);
		
		$app_composer_file = $this->getBasePath().DIRECTORY_SEPARATOR.'composer.json';
		$app_composer_contents = json_decode($fs->get($app_composer_file), true);
		
		$this->assertEquals('*', $app_composer_contents['require']["modules/{$module_name}"]);
		
		$repository = [
			'type' => 'path',
			'url' => "app-modules/{$module_name}",
			'options' => ['symlink' => true],
		];
		$this->assertContains($repository, $app_composer_contents['repositories']);
	}
	
	public function test_it_prompts_on_first_module_if_no_custom_namespace_is_set() : void
	{
		$fs = new Filesystem();
		
		$this->artisan(MakeModule::class, ['name' => 'test-module'])
			->expectsConfirmation('Would you like to cancel and configure your module namespace first?', 'no')
			->assertExitCode(0);
		
		Modules::reload();
		
		$this->assertTrue($fs->isDirectory($this->getBasePath().DIRECTORY_SEPARATOR.'app-modules'.DIRECTORY_SEPARATOR.'test-module'));
		
		$this->artisan(MakeModule::class, ['name' => 'test-module-two'])
			->assertExitCode(0);
		
		$this->assertTrue($fs->isDirectory($this->getBasePath().DIRECTORY_SEPARATOR.'app-modules'.DIRECTORY_SEPARATOR.'test-module-two'));
	}
	
	protected function getBasePath()
	{
		if (null === $this->base_path) {
			$fs = new Filesystem();
			
			$testbench_base_path = parent::getBasePath();
			$this->base_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.md5(__FILE__.microtime(true));
			
			$fs->copyDirectory($testbench_base_path, $this->base_path);
		}
		
		return $this->base_path;
	}
}
