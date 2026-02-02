<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\Cache;
use InterNACHI\Modular\Support\PluginDataRepository;
use InterNACHI\Modular\Support\PluginHandler;

class ModulesClear extends Command
{
	protected $signature = 'modules:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(Cache $cache, PluginDataRepository $data)
	{
		$cache->clear();
		$data->reset();
		
		$this->info('Module cache cleared!');
	}
}
