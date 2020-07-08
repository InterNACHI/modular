<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;

class ModuleList extends Command
{
	protected $signature = 'module:list';
	
	protected $description = 'List all modules';
	
	public function handle(ModuleRegistry $registry)
	{
		$namespace_title = 'Namespace';
		
		$table = $registry->modules()
			->map(function(ModuleConfig $config) use (&$namespace_title) {
				$namespaces = $config->namespaces->map(function($namespace) {
					return rtrim($namespace, '\\');
				});
				
				if ($config->namespaces->count() > 1) {
					$namespace_title = 'Namespaces';
				}
				
				return [
					$config->name,
					Str::after($config->base_path, $this->laravel->basePath().DIRECTORY_SEPARATOR),
					$namespaces->implode(', '),
				];
			})
			->toArray();
		
		$count = $registry->modules()->count();
		$this->line('You have '.$count.' '.Str::plural('module', $count).' installed.');
		$this->line('');
		
		$this->table(['Module', 'Path', $namespace_title], $table);
	}
}
