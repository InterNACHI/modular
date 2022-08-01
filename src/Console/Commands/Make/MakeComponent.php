<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ComponentMakeCommand;

class MakeComponent extends ComponentMakeCommand
{
	use Modularize;
	
	protected function viewPath($path = '')
	{
		if ($module = $this->module()) {
			return $module->path("resources/views/{$path}");
		}
		
		return parent::viewPath($path);
	}
}
