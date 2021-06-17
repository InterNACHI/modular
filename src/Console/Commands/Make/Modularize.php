<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Symfony\Component\Console\Input\InputOption;

trait Modularize
{
	protected function module(): ?ModuleConfig
	{
		return $this->getLaravel()
			->make(ModuleRegistry::class)
			->module($this->option('module'));
	}

	protected function configure()
	{
		parent::configure();

		$this->getDefinition()->addOption(
			new InputOption(
				'--module',
				null,
				InputOption::VALUE_REQUIRED,
				'Create this resource inside an application module'
			)
		);
	}

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
				return DIRECTORY_SEPARATOR.trim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			};

			$find = array_map($normalize, array_keys($replacements));
			$replace = array_map($normalize, array_values($replacements));

			// And finally apply the replacements
			$path = str_replace($find, $replace, $path);
		}

		return $path;
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
