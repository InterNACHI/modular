<?php

namespace InterNACHI\Modular\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use SimpleXMLElement;

class UpdatePhpStormConfig extends Command
{
	protected $signature = 'module:update-phpstorm-config {--dump}';
	
	protected $description = 'Update the PhpStorm Laravel plugin config with module data.';
	
	public function handle(ModuleRegistry $module_registry): int
	{
		$config_path = $this->getLaravel()->basePath('.idea/laravel-plugin.xml');
		
		if (!$this->checkConfigFilePermissions($config_path)) {
			return 1;
		}
		
		$plugin_config = $this->getNormalizedPluginConfig($config_path);
		$template_paths = $plugin_config->xpath('//templatePath');
		
		// Clean up template paths to prevent duplicates
		foreach ($template_paths as $template_path_key => $existing) {
			if (null !== $module_registry->module((string) $existing['namespace'])) {
				unset($template_paths[$template_path_key][0]);
			}
		}
		
		// Now add all modules to the config
		$modules_directory = config('app-modules.modules_directory', 'app-modules');
		$list = $plugin_config->xpath('//option[@name="templatePaths"]//list')[0];
		$module_registry->modules()
			->sortBy('name')
			->each(function(ModuleConfig $module_config) use ($list, $modules_directory) {
				$node = $list->addChild('templatePath');
				$node->addAttribute('namespace', $module_config->name);
				$node->addAttribute('path', "{$modules_directory}/{$module_config->name}/resources/views");
			});
		
		// Format the XML
		$xml = $this->formatXml($plugin_config);
		
		if ($this->option('dump')) {
			$this->info($xml);
			return 1;
		}
		
		$this->info('Writing PhpStorm Laravel plugin config file...');
		file_put_contents($config_path, $xml);
		
		return 0;
	}
	
	protected function formatXml(SimpleXMLElement $xml): string
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		
		$xml = $dom->saveXML();
		$xml = preg_replace('~(\S)/>\s*$~m', '$1 />', $xml);
		
		return $xml;
	}
	
	protected function getNormalizedPluginConfig($config_path): SimpleXMLElement
	{
		$config = simplexml_load_string(file_get_contents($config_path));
		
		// Ensure that <component name="LaravelPluginSettings"> exists
		$component = $config->xpath('//component[@name="LaravelPluginSettings"]');
		if (empty($component)) {
			$component = $config->addChild('component');
			$component->addAttribute('name', 'LaravelPluginSettings');
		} else {
			$component = $component[0];
		}
		
		// Ensure that <option name="templatePaths"> exists
		$template_paths = $component->xpath('//option[@name="templatePaths"]');
		if (empty($template_paths)) {
			$template_paths = $component->addChild('option');
			$template_paths->addAttribute('name', 'templatePaths');
		} else {
			$template_paths = $template_paths[0];
		}
		
		// Ensure that <list> exists inside template paths config
		$list = $template_paths->xpath('//list');
		if (empty($list)) {
			$template_paths->addChild('list');
		}
		
		return $config;
	}
	
	protected function checkConfigFilePermissions(string $config_path): bool
	{
		if (!is_readable($config_path) || !is_writable($config_path)) {
			$this->error("Unable to find or read: '{$config_path}'");
			return false;
		}
		
		if (!is_writable($config_path)) {
			$this->error("Config file is not writable: '{$config_path}'");
			return false;
		}
		
		return true;
	}
}
