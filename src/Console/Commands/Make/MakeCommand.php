<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Support\Str;

class MakeCommand extends ConsoleMakeCommand
{
	use Modularize;
	
	protected function replaceClass($stub, $name)
	{
		$stub = parent::replaceClass($stub, $name);
		
		if ($module = $this->module()) {
			$cli_name = Str::kebab(preg_replace('/Command$/', '', class_basename($name)));
			$stub = str_replace('command:name', "{$module->name}:{$cli_name}", $stub);
		}
		
		return $stub;
	}
}
