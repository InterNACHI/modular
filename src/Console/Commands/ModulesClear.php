<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\AutodiscoveryHelper;

class ModulesClear extends Command
{
	protected $signature = 'modules:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(AutodiscoveryHelper $helper)
	{
		$helper->clearCache();
		
		$this->info('Module cache cleared!');
	}
}
