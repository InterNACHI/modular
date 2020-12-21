<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Encryption\Encrypter;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModularizedCommandsServiceProvider;
use InterNACHI\Modular\Support\ModularServiceProvider;
use InterNACHI\Modular\Support\ModuleConfig;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
	protected function setUp() : void
	{
		parent::setUp();
		
		Modules::clear();
		
		$config = $this->app['config'];
		
		// Add encryption key for HTTP tests
		$config->set('app.key', 'base64:'.base64_encode(Encrypter::generateKey('AES-128-CBC')));
		
		// Add stubs to view
		// $this->app['view']->addLocation(__DIR__.'/Feature/stubs');
	}
	
	protected function makeModule(string $name = 'test-module'): ModuleConfig
	{
		$this->artisan(MakeModule::class, [
			'name' => $name,
			'--accept-default-namespace' => true,
		]);
		
		return Modules::module($name);
	}
	
	protected function getPackageProviders($app)
	{
		return [
			ModularServiceProvider::class,
			ModularizedCommandsServiceProvider::class,
		];
	}
	
	protected function getPackageAliases($app)
	{
		return [
			'Modules' => Modules::class,
		];
	}
}
