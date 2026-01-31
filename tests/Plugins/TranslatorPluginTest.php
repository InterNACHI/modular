<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Support\Facades\Lang;
use InterNACHI\Modular\Tests\Concerns\PreloadsAppModules;
use InterNACHI\Modular\Tests\TestCase;

class TranslatorPluginTest extends TestCase
{
	use PreloadsAppModules;

	public function test_php_translation_namespace_is_registered(): void
	{
		$this->assertTrue(Lang::has('test-module::messages.welcome'));
	}

	public function test_php_translations_are_loaded(): void
	{
		$this->assertEquals(
			'Welcome to Test Module',
			Lang::get('test-module::messages.welcome')
		);
	}

	public function test_php_translations_with_replacements_work(): void
	{
		$this->assertEquals(
			'Hello, John!',
			Lang::get('test-module::messages.greeting', ['name' => 'John'])
		);
	}

	public function test_json_translations_are_loaded(): void
	{
		$translator = $this->app['translator'];
		$translator->setLocale('en');

		$this->assertEquals(
			'Hello from JSON',
			$translator->get('Hello')
		);
	}
}
