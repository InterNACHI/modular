<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\ModuleRegistry;
use InterNACHI\Modular\Support\PhpStormConfigWriter;

class ModuleSync extends Command
{
	protected $name = 'module:sync {--no-phpstorm : Don\'t update PhpStorm config files}';
	
	protected $description = 'Sync your project\'s configuration with your current modules';
	
	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $filesystem;
	
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $registry;
	
	public function handle(ModuleRegistry $registry, Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
		$this->registry = $registry;
		
		$this->updatePhpUnit();
		
		if (true !== $this->option('no-phpstorm')) {
			$this->updatePhpStormConfig();
		}
	}
	
	protected function updatePhpUnit(): void
	{
		$config_path = $this->getLaravel()->basePath('phpunit.xml');
		
		if (!$this->filesystem->exists($config_path)) {
			$this->warn('No phpunit.xml file found. Skipping PHPUnit configuration.');
			return;
		}
		
		$modules_directory = config('app-modules.modules_directory', 'app-modules');
		
		$config = simplexml_load_string($this->filesystem->get($config_path));
		
		$existing_nodes = $config->xpath("//phpunit//testsuites//testsuite//directory[text()='./{$modules_directory}/*/tests']");
		
		if (count($existing_nodes)) {
			$this->info('Modules test suite already exists in phpunit.xml');
			return;
		}
		
		$testsuites = $config->xpath('//phpunit//testsuites');
		if (!count($testsuites)) {
			$this->error('Cannot find <testsuites> node in phpunit.xml file. Skipping PHPUnit configuration.');
			return;
		}
		
		$testsuite = $testsuites[0]->addChild('testsuite');
		$testsuite->addAttribute('name', 'Modules');
		
		$directory = $testsuite->addChild('directory');
		$directory->addAttribute('suffix', 'Test.php');
		$directory[0] = "./{$modules_directory}/*/tests";
		
		$config->formatOutput = true;
		
		$this->filesystem->put($config_path, $config->asXML());
		$this->info('Added "Modules" PHPUnit test suite.');
	}
	
	protected function updatePhpStormConfig(): void
	{
		$config_path = $this->getLaravel()->basePath('.idea/laravel-plugin.xml');
		$writer = new PhpStormConfigWriter($config_path, $this->registry);
		
		if ($writer->write()) {
			$this->info('Updated PhpStorm/Laravel Plugin config file...');
		} else {
			$this->info('Did not find/update PhpStorm/Laravel Plugin config.');
			if ($this->getOutput()->isVerbose()) {
				$this->warn($writer->last_error);
			}
		}
	}
}
