<?php

namespace InterNACHI\Modular\Support;

use DOMDocument;
use SimpleXMLElement;

class PhpStormConfigWriter
{
	/**
	 * @var string
	 */
	public $last_error;
	
	/**
	 * @var string
	 */
	protected $config_path;
	
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $module_registry;
	
	public function __construct($config_path, ModuleRegistry $module_registry)
	{
		$this->config_path = $config_path;
		$this->module_registry = $module_registry;
	}
	
	public function write(): bool
	{
		if (!$this->checkConfigFilePermissions()) {
			return false;
		}
		
		$plugin_config = $this->getNormalizedPluginConfig();
		$template_paths = $plugin_config->xpath('//templatePath');
		
		// Clean up template paths to prevent duplicates
		foreach ($template_paths as $template_path_key => $existing) {
			if (null !== $this->module_registry->module((string) $existing['namespace'])) {
				unset($template_paths[$template_path_key][0]);
			}
		}
		
		// Now add all modules to the config
		$modules_directory = config('app-modules.modules_directory', 'app-modules');
		$list = $plugin_config->xpath('//option[@name="templatePaths"]//list')[0];
		$this->module_registry->modules()
			->sortBy('name')
			->each(function(ModuleConfig $module_config) use ($list, $modules_directory) {
				$node = $list->addChild('templatePath');
				$node->addAttribute('namespace', $module_config->name);
				$node->addAttribute('path', "{$modules_directory}/{$module_config->name}/resources/views");
			});
		
		return false !== file_put_contents($this->config_path, $this->formatXml($plugin_config));
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
	
	protected function getNormalizedPluginConfig(): SimpleXMLElement
	{
		$config = simplexml_load_string(file_get_contents($this->config_path));
		
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
	
	protected function checkConfigFilePermissions(): bool
	{
		if (!is_readable($this->config_path) || !is_writable($this->config_path)) {
			return $this->error("Unable to find or read: '{$this->config_path}'");
		}
		
		if (!is_writable($this->config_path)) {
			return $this->error("Config file is not writable: '{$this->config_path}'");
		}
		
		return true;
	}
	
	protected function error(string $message): bool 
	{
		$this->last_error = $message;
		return false;
	}
}
