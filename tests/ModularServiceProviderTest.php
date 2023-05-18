<?php

namespace InterNACHI\Modular\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;

class ModularServiceProviderTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_registry_is_bound_as_a_singleton(): void
	{
		$registry = $this->app->make(ModuleRegistry::class);
		$registry2 = $this->app->make(ModuleRegistry::class);
		
		$this->assertInstanceOf(ModuleRegistry::class, $registry);
		$this->assertSame($registry, $registry2);
	}
	
	public function test_model_factory_classes_are_resolved_correctly(): void
	{
		$this->requiresLaravelVersion('8.0.0');
		
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
	
	public function test_model_factory_classes_are_resolved_correctly_with_custom_namespace(): void
	{
		$this->requiresLaravelVersion('8.0.0');
		
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
	
	public function test_model_classes_are_resolved_correctly_for_factories_with_custom_namespace(): void
	{
		$this->requiresLaravelVersion('8.0.0');
		
		$module = $this->makeModule();
		
		// We'll create a factory and instantiate it
		$this->artisan('make:model', ['name' => 'Widget', '--factory' => true, '--module' => $module->name]);
		require $module->path('database/factories/WidgetFactory.php');
		$factory_class = $module->qualify('Database\\Factories\\WidgetFactory');
		$factory = new $factory_class();
		
		/** @var Factory $factory */
		$this->assertEquals(
			$module->qualify('Models\\Widget'),
			$factory->modelName(),
		);
		
		// We'll also confirm that non-app factories are unaffected
		$this->artisan('make:model', ['name' => 'Widget', '--factory' => true]);
		require database_path('factories/WidgetFactory.php');
		$factory = new \Database\Factories\WidgetFactory();
		
		$this->assertEquals(
			'App\\Widget',
			$factory->modelName(),
		);
	}
	
	public function test_it_loads_translations_from_module(): void
	{
		$module = $this->makeModule();
		
		$this->filesystem()->ensureDirectoryExists($module->path('resources/lang'));
		$this->filesystem()->ensureDirectoryExists($module->path('resources/lang/en'));
		
		$this->filesystem()->put($module->path('resources/lang/en.json'), json_encode([
			'Test JSON string' => 'Test JSON translation',
		], JSON_THROW_ON_ERROR));
		
		$this->filesystem()->put(
			$module->path('resources/lang/en/foo.php'),
			'<?php return ["bar" => "Test PHP translation"];'
		);
		
		$this->app->setLocale('en');
		
		$translator = $this->app->make('translator');
		
		$this->assertEquals('Test JSON translation', $translator->get('Test JSON string'));
		$this->assertEquals('Test PHP translation', $translator->get('test-module::foo.bar'));
	}
}
