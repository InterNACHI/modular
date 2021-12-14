<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\CacheHelper;

class ModulesClear extends Command
{
	protected $signature = 'modules:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(CacheHelper $helper)
	{
		if ($helper->delete()) {
			$this->info('Module cache cleared!');
		} else {
			$this->error('Unable to clear cache!');
			return 1;
		}
	}
}
