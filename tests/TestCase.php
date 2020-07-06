<?php

namespace InterNACHI\Modular\Tests;

use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModularizedCommandsServiceProvider;
use InterNACHI\Modular\Support\ModularServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
	protected function setUp() : void
	{
		parent::setUp();
		
		$config = $this->app['config'];
		
		// Add encryption key for HTTP tests
		$config->set('app.key', 'base64:tfsezwCu4ZRixRLA/+yL/qoouX++Q3lPAPOAbtnBCG8=');
		
		// Add feature stubs to view
		$this->app['view']->addLocation(__DIR__.'/Feature/stubs');
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
