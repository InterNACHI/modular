<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeModuleTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_scaffolds_a_new_module(): void
	{
		$module_name = 'test-module';
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		]);
		
		$fs = $this->filesystem();
		$module_path = $this->getApplicationBasePath().'/app-modules/'.$module_name;
		
		$this->assertTrue($fs->isDirectory($module_path));
		$this->assertTrue($fs->isDirectory($module_path.'/database'));
		$this->assertTrue($fs->isDirectory($module_path.'/resources'));
		$this->assertTrue($fs->isDirectory($module_path.'/routes'));
		$this->assertTrue($fs->isDirectory($module_path.'/src'));
		$this->assertTrue($fs->isDirectory($module_path.'/tests'));
		
		$composer_file = $module_path.'/composer.json';
		$this->assertTrue($fs->isFile($composer_file));
		
		$composer_contents = json_decode($fs->get($composer_file), true);
		
		$this->assertEquals("modules/{$module_name}", $composer_contents['name']);
		$this->assertEquals('src/', $composer_contents['autoload']['psr-4']['Modules\\TestModule\\']);
		$this->assertEquals('tests/', $composer_contents['autoload']['psr-4']['Modules\\TestModule\\Tests\\']);
		$this->assertContains('Modules\\TestModule\\Providers\\TestModuleServiceProvider', $composer_contents['extra']['laravel']['providers']);
		
		$this->assertEquals('database/factories/', $composer_contents['autoload']['psr-4']['Modules\\TestModule\\Database\\Factories\\']);
		$this->assertEquals('database/seeders/', $composer_contents['autoload']['psr-4']['Modules\\TestModule\\Database\\Seeders\\']);
		
		$app_composer_file = $this->getApplicationBasePath().'/composer.json';
		$app_composer_contents = json_decode($fs->get($app_composer_file), true);
		
		$this->assertEquals('*', $app_composer_contents['require']["modules/{$module_name}"]);
		
		$repository = [
			'type' => 'path',
			'url' => 'app-modules/*',
			'options' => ['symlink' => true],
		];
		$this->assertContains($repository, $app_composer_contents['repositories']);
	}
	
	public function test_it_scaffolds_a_new_module_based_on_custom_config(): void
	{
		$fs = $this->filesystem();
		
		$module_name = 'test-module';
		
		config()->set('app-modules.stubs', [
			'src/StubClassNamePrefixInfo.php' => str_replace('\\', '/', dirname(__DIR__, 2)).'/stubs/test-stub.php',
		]);
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		]);
		
		$path = $this->getModulePath($module_name, '/src/TestModuleInfo.php');
		
		$this->assertTrue($fs->isFile($path));
		$this->assertStringContainsString($module_name, $fs->get($path));
	}
	
	public function test_it_prompts_on_first_module_if_no_custom_namespace_is_set(): void
	{
		$fs = $this->filesystem();
		
		$this->artisan(MakeModule::class, ['name' => 'test-module'])
			->expectsQuestion('Would you like to cancel and configure your module namespace first?', false)
			->assertExitCode(0);
		
		Modules::reload();
		
		$this->assertTrue($fs->isDirectory($this->getApplicationBasePath().'/app-modules/test-module'));
		
		$this->artisan(MakeModule::class, ['name' => 'test-module-two'])
			->assertExitCode(0);
		
		$this->assertTrue($fs->isDirectory($this->getApplicationBasePath().'/app-modules/test-module-two'));
	}

	public function test_it_does_not_create_an_empty_directory_if_prompt_on_first_module_if_no_custom_namespace_is_set_is_rejected(): void
	{
		$fs = $this->filesystem();

		$this->artisan(MakeModule::class, ['name' => 'test-module'])
			->expectsQuestion('Would you like to cancel and configure your module namespace first?', true)
			->assertExitCode(0);

		Modules::reload();

		$this->assertFalse($fs->isDirectory($this->getApplicationBasePath().'/app-modules/test-module'));
	}
}
