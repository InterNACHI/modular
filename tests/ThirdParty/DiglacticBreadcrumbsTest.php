<?php

namespace InterNACHI\Modular\Tests\ThirdParty;

use Illuminate\Support\Str;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class DiglacticBreadcrumbsTest extends TestCase
{
	use WritesToAppFilesystem;
	
	protected $module;
	
	protected function setUp() : void
	{
		parent::setUp();
		
		if (!class_exists('Diglactic\\Breadcrumbs\\Manager')) {
			static::markTestSkipped("'diglactic/laravel-breadcrumbs' is not installed.");
		}
		
		$this->module = $this->makeModule('test-module');
	}
	
	public function test_it_loads_breadcrumbs_if_package_is_installed() : void
	{
		$destination = Str::after($this->getBasePath(), $this->module->path('routes/breadcrumbs'));
		$this->copyStub('breadcrumbs.php', $destination);
		
		$breadcrumbs = \Diglactic\Breadcrumbs\Breadcrumbs::generate('home');
		
		$this->assertEquals(1, $breadcrumbs->count());
		$this->assertEquals('Home', $breadcrumbs->first()->title);
	}
}
