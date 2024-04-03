<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Support\Str;

class MakeCommand extends ConsoleMakeCommand
{
	use Modularize;
	
	protected function replaceClass($stub, $name)
	{
		$module = $this->module();
		
		$stub = parent::replaceClass($stub, $name);
		
		if ($module) {
			$cli_name = Str::of($name)->classBasename()->kebab();
			
			$find = [
				'{{command}}',
				'{{ command }}',
				'dummy:command',
				'command:name',
				"app:{$cli_name}",
			];
			
			$stub = str_replace($find, "{$module->name}:{$cli_name}", $stub);
		}
		
		return $stub;
	}
}
