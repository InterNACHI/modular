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
		
		if ($module && 'command:name' === ($this->option('command') ?: 'command:name')) {
			$cli_name = Str::of($name)->classBasename()->kebab();
			
			$find = [
				"{{ command }}",
				"dummy:command",
				"command:name",
				"app:{$cli_name}",
			];
			
			$stub = str_replace($find, "{$module->name}:{$cli_name}", $stub);
		}
		
		return parent::replaceClass($stub, $name);
	}
}
