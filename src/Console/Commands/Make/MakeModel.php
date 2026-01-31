<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Str;

class MakeModel extends ModelMakeCommand
{
	use Modularize;
	
	protected function getDefaultNamespace($rootNamespace)
	{
		if ($module = $this->module()) {
			$rootNamespace = rtrim($module->namespaces->first(), '\\');
		}
		
		return $rootNamespace.'\Models';
	}
	
	protected function buildFactoryReplacements()
	{
		$replacements = parent::buildFactoryReplacements();
		
		if ($module = $this->module()) {
			$replacements['{{ factory }}'] = str_replace(
				'\\Database\\Factories\\', 
				'\\'.$module->namespace().'Database\\Factories\\',
				$replacements['{{ factory }}'],
			);
		}
		
		return $replacements;
	}
}
