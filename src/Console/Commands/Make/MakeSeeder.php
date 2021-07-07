<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Database\Console\Seeds\SeederMakeCommand;

class MakeSeeder extends SeederMakeCommand
{
	use Modularize;
	
	protected function replaceNamespace(&$stub, $name)
	{
		if ($module = $this->module()) {
			$namespace = $module->qualify('Database\Seeders');
			$stub = str_replace('namespace Database\Seeders;', "namespace {$namespace};", $stub);
		}
		
		return parent::replaceNamespace($stub, $name);
	}
}
