<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;

class ModularServiceProviderTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_registry_is_bound_as_a_singleton() : void
	{
		$registry = $this->app->make(ModuleRegistry::class);
		$registry2 = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleRegistry::class, $registry);
		$this->assertSame($registry, $registry2);
	}
	
	public function test_model_factory_classes_are_resolved_correctly() : void
	{
		$module = $this->makeModule();
		
		$this->assertEquals(
			$module->qualify('Database\\Factories\\FooFactory'),
			Factory::resolveFactoryName($module->qualify('Models\\Foo'))
		);
		
		$this->assertEquals(
			$module->qualify('Database\\Factories\\FooFactory'),
			Factory::resolveFactoryName($module->qualify('Foo'))
		);
		
		$this->assertEquals(
			$module->qualify('Database\\Factories\\Foo\\BarFactory'),
			Factory::resolveFactoryName($module->qualify('Models\\Foo\\Bar'))
		);
		
		$this->assertEquals(
			$module->qualify('Database\\Factories\\Foo\\BarFactory'),
			Factory::resolveFactoryName($module->qualify('Foo\\Bar'))
		);
		
		$this->assertEquals(
			'Database\\Factories\\FooFactory',
			Factory::resolveFactoryName('App\\Models\\Foo')
		);
		
		$this->assertEquals(
			'Database\\Factories\\FooFactory',
			Factory::resolveFactoryName('App\\Foo')
		);
		
		$this->assertEquals(
			'Database\\Factories\\Foo\\BarFactory',
			Factory::resolveFactoryName('App\\Models\\Foo\\Bar')
		);
		
		$this->assertEquals(
			'Database\\Factories\\Foo\\BarFactory',
			Factory::resolveFactoryName('App\\Foo\\Bar')
		);
	}
	
	public function test_model_factory_classes_are_resolved_correctly_with_custom_namespace() : void
	{
		Factory::useNamespace('Something\\');
		
		$module = $this->makeModule();
		
		$this->assertEquals(
			$module->qualify('Something\\FooFactory'),
			Factory::resolveFactoryName($module->qualify('Models\\Foo'))
		);
		
		$this->assertEquals(
			$module->qualify('Something\\FooFactory'),
			Factory::resolveFactoryName($module->qualify('Foo'))
		);
		
		$this->assertEquals(
			$module->qualify('Something\\Foo\\BarFactory'),
			Factory::resolveFactoryName($module->qualify('Models\\Foo\\Bar'))
		);
		
		$this->assertEquals(
			$module->qualify('Something\\Foo\\BarFactory'),
			Factory::resolveFactoryName($module->qualify('Foo\\Bar'))
		);
		
		$this->assertEquals(
			'Something\\FooFactory',
			Factory::resolveFactoryName('App\\Models\\Foo')
		);
		
		$this->assertEquals(
			'Something\\FooFactory',
			Factory::resolveFactoryName('App\\Foo')
		);
		
		$this->assertEquals(
			'Something\\Foo\\BarFactory',
			Factory::resolveFactoryName('App\\Models\\Foo\\Bar')
		);
		
		$this->assertEquals(
			'Something\\Foo\\BarFactory',
			Factory::resolveFactoryName('App\\Foo\\Bar')
		);
	}
}
