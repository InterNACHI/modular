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
		$module = $this->module();
		
		if ($module && (! $this->option('command') || 'command:name' === $this->option('command'))) {
			$cli_name = Str::of($name)->classBasename()->kebab();
			
			$find = [
				"signature = 'command:name'",
				"signature = 'app:{$cli_name}'",
			];
			
			$stub = str_replace($find, "signature = '{$module->name}:{$cli_name}'", $stub);
		}
		
		return $stub;
	}
}
