<?php

namespace InterNACHI\Modular\Tests\Plugins;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
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
	
	public function test_custom_translators_that_do_not_extend_the_base_translator_are_supported(): void
	{
		$this->app->forgetInstance('translator');
		$this->app->singleton('translator', fn() => new CustomTranslator());
		
		$translator = $this->app->make('translator');
		
		$this->assertInstanceOf(CustomTranslator::class, $translator);
	}
}

class CustomTranslator implements TranslatorContract
{
	protected string $locale = 'en';
	
	public function get($key, array $replace = [], $locale = null)
	{
		return $key;
	}
	
	public function choice($key, $number, array $replace = [], $locale = null)
	{
		return $key;
	}
	
	public function getLocale()
	{
		return $this->locale;
	}
	
	public function setLocale($locale)
	{
		$this->locale = $locale;
	}
}
