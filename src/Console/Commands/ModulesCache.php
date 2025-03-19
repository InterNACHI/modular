<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\AutodiscoveryHelper;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use LogicException;
use Throwable;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(AutodiscoveryHelper $helper)
	{
		$this->call(ModulesClear::class);
		
		$helper->writeCache($this->getLaravel());
		
		$this->info('Modules cached successfully!');
	}
}
