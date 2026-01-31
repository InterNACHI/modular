<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Console\ComponentMakeCommand;

class MakeComponent extends ComponentMakeCommand
{
	use Modularize;
	
	protected function buildClass($name)
	{
		$class = parent::buildClass($name);
		
		if ($module = $this->module()) {
			$view = $this->getView();
			$class = str_replace(
				"'{$view}'",
				"'{$module->name}::{$view}'", 
				$class
			);
		}
		
		return $class;
	}
	
	protected function viewPath($path = '')
	{
		if ($module = $this->module()) {
			return $module->path("resources/views/{$path}");
		}
		
		return parent::viewPath($path);
	}
}
