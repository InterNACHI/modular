<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use LogicException;
use Throwable;

class ModulesCache extends Command
{
	protected $signature = 'modules:cache';
	
	protected $description = 'Create a cache file for faster module loading';
	
	public function handle(ModuleRegistry $registry, Filesystem $filesystem)
	{
		$this->call(ModulesClear::class);
		
		$export = $registry->modules()
			->map(function(ModuleConfig $module_config) {
				return $module_config->toArray();
			})
			->toArray();
		
		$cache_path = $registry->getCachePath();
		$cache_contents = '<?php return '.var_export($export, true).';'.PHP_EOL;
		
		$filesystem->put($cache_path, $cache_contents);
		
		try {
			require $cache_path;
		} catch (Throwable $e) {
			$filesystem->delete($cache_path);
			throw new LogicException('Unable to cache module configuration.', 0, $e);
		}
		
		$this->info('Modules cached successfully!');
	}
}
