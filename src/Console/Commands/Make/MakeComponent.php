<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ComponentMakeCommand;

class MakeComponent extends ComponentMakeCommand
{
	use Modularize;
	
	protected function viewPath($path = '')
	{
		if ($module = $this->module()) {
			$sep = DIRECTORY_SEPARATOR;
			return $module->path("resources{$sep}views{$sep}{$path}");
		}
		
		return parent::viewPath($path);
	}
}
