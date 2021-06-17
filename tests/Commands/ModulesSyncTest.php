<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\ModulesSync;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class ModulesSyncTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_updates_phpunit_config(): void
	{
		$config_path = $this->copyStub('phpunit.xml', '/');
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./app-modules/*/tests']");
		
		$this->assertCount(0, $nodes);
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./app-modules/*/tests']");
		
		$this->assertCount(1, $nodes);
	}
	
	public function test_it_updates_phpstorm_plugin_config(): void
	{
		$config_path = $this->copyStub('laravel-plugin.xml', '.idea');
		
		$this->makeModule('test-module');
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');
		
		$this->assertCount(0, $nodes);
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');
		
		$this->assertCount(1, $nodes);
		
		$this->makeModule('test-module-two');
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="LaravelPluginSettings"]//option[@name="templatePaths"]//list//templatePath');
		
		$this->assertCount(2, $nodes);
	}
	
	public function test_it_updates_phpstorm_library_roots(): void
	{
		$config_path = $this->copyStub('php.xml', '.idea');
		
		$this->makeModule('test-module');
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');
		
		$this->assertCount(1, $nodes);
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path[@value="$PROJECT_DIR$/vendor/modules/test-module"]');
		
		$this->assertCount(0, $nodes);
	}
	
	public function test_it_updates_phpstorm_iml_file(): void
	{
		$config_path = $this->copyStub('project.iml', '.idea');
		
		$this->makeModule('test-module');
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');
		
		$this->assertCount(4, $nodes);
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');
		
		$this->assertCount(4, $nodes);
		
		$this->makeModule('test-module-two');
		
		$this->artisan(ModulesSync::class);
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		$nodes = $config->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');
		
		$this->assertCount(6, $nodes);
	}
}
