<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Route;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class RoutesPluginTest extends TestCase
{
	use PreloadsAppModules;

	public function test_module_routes_are_loaded(): void
	{
		$routes = Route::getRoutes();
		$route = $routes->getByName('test-module.index');

		$this->assertNotNull($route);
		$this->assertEquals('test-module', $route->uri());
	}

	public function test_named_route_is_accessible(): void
	{
		$this->assertTrue(Route::has('test-module.index'));
	}

	public function test_route_responds_correctly(): void
	{
		$response = $this->get('/test-module');

		$response->assertOk();
		$response->assertSee('Hello from test-module');
	}
}
