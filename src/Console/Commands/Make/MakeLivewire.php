<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Support\Facades\Config;
use Livewire\Commands\MakeCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeLivewire extends MakeCommand
{
	use Modularize;
	
	public function handle()
	{
		if ($module = $this->module()) {
			Config::set('livewire.class_namespace', $module->qualify('Http\\Livewire'));
			Config::set('livewire.view_path', $module->path('resources/views/livewire'));
		}
		
		parent::handle();
	}
}
