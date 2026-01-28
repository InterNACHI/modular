<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Support\Str;

class MakeSeeder extends SeederMakeCommand
{
	use Modularize {
		getPath as getModularPath;
	}
	
	protected function getPath($name)
	{
		if ($module = $this->module()) {
			$name = Str::replaceFirst($module->qualify('Database\\Seeders\\'), '', $name);
			return $this->getModularPath($name);
		}
		
		return parent::getPath($name);
	}
	
	protected function replaceNamespace(&$stub, $name)
	{
		return parent::replaceNamespace($stub, $name);
	}
	
	protected function rootNamespace()
	{
		if ($module = $this->module()) {
			return $module->qualify('Database\Seeders');
		}

		return parent::rootNamespace();
	}
}
