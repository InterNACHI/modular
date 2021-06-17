<?php

namespace InterNACHI\Modular\Console\Commands\Make;

use Illuminate\Support\Facades\Config;
use Livewire\Commands\MakeCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeLivewire extends MakeCommand
{
	use Modularize;

	protected $signature = 'livewire:make {name} {--force} {--inline} {--test} {--stub=default}';

	public function __construct()
	{
		parent::__construct();

		$this->getDefinition()->addOption(
			new InputOption(
				'--module',
				null,
				InputOption::VALUE_REQUIRED,
				'Create this resource inside an application module'
			)
		);

		Config::set('livewire.class_namespace', 'Modules\Blueprints');
		Config::set('livewire.view_path', 'app-modules/blueprints/resources/livewire');
	}
}
