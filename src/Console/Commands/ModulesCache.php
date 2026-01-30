<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\AutodiscoveryHelper;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(AutodiscoveryHelper $helper)
	{
		$this->call(ModulesClear::class);
		
		$helper->writeCache();
		
		$this->info('Modules cached successfully!');
	}
}
