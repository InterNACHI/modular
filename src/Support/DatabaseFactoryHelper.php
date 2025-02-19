<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use ReflectionClass;

class DatabaseFactoryHelper
{
	protected ?string $namespace = null;
	
	public function __construct(
		protected ModuleRegistry $registry
	) {
	}
	
	public function resetResolvers(): void
	{
        if (version_compare(\Illuminate\Foundation\Application::VERSION, '11.43.0', '>=')) {
            Factory::flushState();
        } else {
            $this->unsetProperty(Factory::class, 'modelNameResolver');
            $this->unsetProperty(Factory::class, 'modelNameResolvers');
            $this->unsetProperty(Factory::class, 'factoryNameResolver');
        }
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
                $this->unsetProperty(Factory::class, 'modelNameResolvers');
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
		return $this->namespace ??= $this->getProperty(Factory::class, 'namespace');
	}
	
	protected function getProperty($target, $property)
	{
		$reflection = new ReflectionClass($target);
		return $reflection->getStaticPropertyValue($property);
	}
	
	protected function unsetProperty($target, $property): void
	{
		$reflection = new ReflectionClass($target);
        if ($reflection->hasProperty($property)) {
            $reflected = $reflection->getProperty($property);
            $reflection->setStaticPropertyValue($property, $reflected->getDefaultValue() ?? null);
        }
	}
}
