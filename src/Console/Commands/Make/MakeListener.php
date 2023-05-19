<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use InterNACHI\Modular\Support\Facades\Modules;

class MakeListener extends ListenerMakeCommand
{
	use Modularize;
	
	protected function buildClass($name)
	{
		$event = $this->option('event');
		
		if (Modules::moduleForClass($name)) {
			$stub = str_replace(
				['DummyEvent', '{{ event }}'],
				class_basename($event),
				GeneratorCommand::buildClass($name)
			);
			
			return str_replace(
				['DummyFullEvent', '{{ eventNamespace }}'],
				trim($event, '\\'),
				$stub
			);
		}
		
		return parent::buildClass($name);
	}
}
