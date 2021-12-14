<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\Cache;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(AutoDiscoveryHelper $helper, Cache $cache)
	{
		$this->call(ModulesClear::class);
		
		$cache->write($helper->toArray());
		
		if (!$cache->load()) {
			$this->error('Unable to cache module configuration.');
			return 1;
		}
		
		$this->info('Modules cached successfully!');
		return 0;
	}
}
