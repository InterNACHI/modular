<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\Cache;
use InterNACHI\Modular\Support\PluginDataRepository;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(Cache $cache, PluginDataRepository $data)
	{
		$this->call(ModulesClear::class);
		
		$cache->write($data->all());
		
		$this->info('Modules cached successfully!');
	}
}
