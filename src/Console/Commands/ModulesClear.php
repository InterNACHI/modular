<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\CacheHelper;
use InterNACHI\Modular\Support\ModuleRegistry;

class ModulesClear extends Command
{
	protected $signature = 'modules:clear';
	
	protected $description = 'Remove the module cache file';
	
	public function handle(Filesystem $filesystem, CacheHelper $helper)
	{
		$filesystem->delete($helper->getFilename());
		
		$this->info('Module cache cleared!');
	}
}
