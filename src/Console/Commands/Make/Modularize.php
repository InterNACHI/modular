<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Support\Str;

trait Modularize
{
	use \InterNACHI\Modular\Console\Commands\Modularize;
	
	protected function getDefaultNamespace($rootNamespace)
	{
		$namespace = parent::getDefaultNamespace($rootNamespace);
		$module = $this->module();
		
		if ($module && false === strpos($rootNamespace, $module->namespaces->first())) {
			$find = rtrim($rootNamespace, '\\');
			$replace = rtrim($module->namespaces->first(), '\\');
			$namespace = str_replace($find, $replace, $namespace);
		}
		
		return $namespace;
	}
	
	protected function qualifyClass($name)
	{
		$name = ltrim($name, '\\/');
		
		if ($module = $this->module()) {
			if (Str::startsWith($name, $module->namespaces->first())) {
				return $name;
			}
		}
		
		return parent::qualifyClass($name);
	}
	
	protected function qualifyModel(string $model)
	{
		if ($module = $this->module()) {
			$model = str_replace('/', '\\', ltrim($model, '\\/'));
			
			if (Str::startsWith($model, $module->namespace())) {
				return $model;
			}
			
			return $module->qualify('Models\\'.$model);
		}
		
		return parent::qualifyModel($model);
	}
	
protected function getPath($name)
	{
		if ($module = $this->module()) {
			$name = Str::replaceFirst($module->namespaces->first(), '', $name);
		}

		$path = parent::getPath($name);

		if ($module) {
			// Set up our replacements as a [find -> replace] array
			$replacements = [
				$this->laravel->path() => $module->namespaces->keys()->first(),
				$this->laravel->basePath('tests/Tests') => $module->path('tests'),
				$this->laravel->databasePath() => $module->path('database'),
			];

			// Normalize all our paths for compatibility's sake
			$normalize = function($path) {
			        $path = str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
				return trim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			};

			$find = array_map($normalize, array_keys($replacements));
			$replace = array_map($normalize, array_values($replacements));
			// And finally apply the replacements
			$path = str_replace($find, $replace, $normalize($path));
		}

		return rtrim($path, DIRECTORY_SEPARATOR);
	}
	
	public function call($command, array $arguments = [])
	{
		// Pass the --module flag on to subsequent commands
		if ($module = $this->option('module')) {
			$arguments['--module'] = $module;
		}
		
		return $this->runCommand($command, $arguments, $this->output);
	}
}
