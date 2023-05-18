<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use ReflectionProperty;

class DatabaseFactoryHelper
{
	protected $namespace = null;
	
	protected $registry;
	
	public function __construct(ModuleRegistry $registry)
	{
		$this->registry = $registry;
	}
	
	public function modelNameResolver(): Closure
	{
		return function(Factory $factory) {
			if ($module = $this->registry->moduleForClass(get_class($factory))) {
				return (string) Str::of(get_class($factory))
					->replaceFirst($module->qualify($this->namespace()), '')
					->replaceLast('Factory', '')
					->prepend($module->qualify('Models'), '\\');
			}
			
			// Temporarily disable the modular resolver if we're not in a module
			try {
				$this->unsetProperty(Factory::class, 'modelNameResolver');
				return $factory->modelName();
			} finally {
				Factory::guessModelNamesUsing($this->modelNameResolver());
			}
		};
	}
	
	public function factoryNameResolver(): Closure
	{
		return function($model_name) {
			if ($module = $this->registry->moduleForClass($model_name)) {
				$model_name = Str::startsWith($model_name, $module->qualify('Models\\'))
					? Str::after($model_name, $module->qualify('Models\\'))
					: Str::after($model_name, $module->namespace());
				
				return $module->qualify($this->namespace().$model_name.'Factory');
			}
			
			// Temporarily disable the modular resolver if we're not in a module
			try {
				$this->unsetProperty(Factory::class, 'factoryNameResolver');
				return Factory::resolveFactoryName($model_name);
			} finally {
				Factory::guessFactoryNamesUsing($this->factoryNameResolver());
			}
		};
	}
	
	/**
	 * Because Factory::$namespace is protected, we need to access it via reflection.
	 */
	public function namespace(): string
	{
		if (null === $this->namespace) {
			$this->namespace = $this->getProperty(Factory::class, 'namespace');
		}
		
		return $this->namespace;
	}
	
	protected function getProperty($target, $property)
	{
		$reflection = new ReflectionProperty($target, $property);
		
		$reflection->setAccessible(true);
		
		return $reflection->getValue();
	}
	
	protected function unsetProperty($target, $property): void
	{
		$reflection = new ReflectionProperty($target, $property);
		
		$reflection->setAccessible(true);
		
		$reflection->setValue(null);
	}
}
