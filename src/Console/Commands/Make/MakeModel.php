<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ModelMakeCommand;

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
}
