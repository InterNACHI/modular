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
		if ($module = $this->module()) {
			if (version_compare($this->getLaravel()->version(), '9.6.0', '<')) {
				$namespace = $module->qualify('Database\Seeders');
				$stub = str_replace('namespace Database\Seeders;', "namespace {$namespace};", $stub);
			}
		}
		
		return parent::replaceNamespace($stub, $name);
	}
	
	protected function rootNamespace()
	{
		if ($module = $this->module()) {
			if (version_compare($this->getLaravel()->version(), '9.6.0', '>=')) {
				return $module->qualify('Database\Seeders');
			}
		}
		
		return parent::rootNamespace();
	}
}
