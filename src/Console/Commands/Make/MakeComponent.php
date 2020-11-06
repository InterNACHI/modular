<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\ComponentMakeCommand;
use Illuminate\Support\Facades\App;

class MakeComponent extends ComponentMakeCommand
{
	use Modularize;
	
	protected function writeView()
	{
		$module = $this->module();
		$app = $this->getLaravel();
		
		$original_base_path = $app->basePath();
		
		try {
			if ($module && $app instanceof Application) {
				$app->setBasePath($module->path());
			}
			parent::writeView();
		} finally {
			if ($app instanceof Application) {
				$app->setBasePath($original_base_path);
			}
		}
	}
}
