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
		if (!$module = $this->module()) {
			return parent::parseModel($model);
		}
		
		if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
			throw new InvalidArgumentException('Model name contains invalid characters.');
		}
		
		$model = trim(str_replace('/', '\\', $model), '\\');
		
		if (!Str::startsWith($model, $namespace = $module->namespaces->first())) {
			$model = $namespace.$model;
		}
		
		return $model;
	}
}
