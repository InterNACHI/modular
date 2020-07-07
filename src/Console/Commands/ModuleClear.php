<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\ModuleRegistry;

class ModuleClear extends Command
{
	protected $signature = 'module:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(Filesystem $filesystem, ModuleRegistry $registry)
	{
		$filesystem->delete($registry->getCachePath());
		$this->info('Module cache cleared!');
	}
}
