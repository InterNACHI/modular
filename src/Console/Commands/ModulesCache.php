<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use LogicException;
use Throwable;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(AutoDiscoveryHelper $helper, Filesystem $filesystem)
	{
		$this->call(ModulesClear::class);
		
		$helper->writeCache();
		$helper->reloadCache();
		
		// FIXME:
		// try {
		// 	require $cache_path;
		// } catch (Throwable $e) {
		// 	$filesystem->delete($cache_path);
		// 	throw new LogicException('Unable to cache module configuration.', 0, $e);
		// }
		
		$this->info('Modules cached successfully!');
	}
}
