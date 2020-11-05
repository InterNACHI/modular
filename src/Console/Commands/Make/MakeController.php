<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MakeController extends ControllerMakeCommand
{
	use Modularize;
	
	protected function parseModel($model)
	{
		if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
			throw new InvalidArgumentException('Model name contains invalid characters.');
		}
		
		$model = trim(str_replace('/', '\\', $model), '\\');
		
		if ($module = $this->module()) {
			$module_namespace = $module->namespaces->first();
			if (!Str::startsWith($model, $module_namespace)) {
				return $module_namespace.$model;
			}
		}
		
		return parent::parseModel($model);
	}
}
