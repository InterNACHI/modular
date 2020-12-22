<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\AutoDiscoveryHelper;
use InterNACHI\Modular\Support\CacheHelper;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use LogicException;
use Throwable;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(AutoDiscoveryHelper $helper, CacheHelper $cache)
	{
		$this->call(ModulesClear::class);
		
		foreach ($helper->toArray() as $key => $value) {
			$cache->set($key, $value);
		}
		
		$filename = $cache->getFilename();
		
		if (!$cache->write()) {
			throw new LogicException("Unable to write to '{$filename}'.");
		}
		
		try {
			require $filename;
		} catch (Throwable $e) {
			if (file_exists($filename)) {
				@unlink($filename);
			}
			throw new LogicException('Unable to cache module configuration.', 0, $e);
		}
		
		$this->info('Modules cached successfully!');
	}
}
