<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\Cache;

class ModulesClear extends Command
{
	protected $signature = 'modules:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(AutoDiscoveryHelper $helper, Cache $cache)
	{
		if ($cache->delete() && $helper->clear()) {
			$this->info('Module cache cleared!');
		} else {
			$this->error('Unable to clear cache!');
			return 1;
		}
	}
}
