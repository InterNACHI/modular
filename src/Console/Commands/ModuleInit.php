<?php

namespace InterNACHI\Modular\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\ModuleRegistry;

class ModuleInit extends Command
{
	protected $name = 'module:init';
	
	protected $description = 'Initialize modular support in project';
	
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
}
