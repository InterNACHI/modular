<?php

namespace InterNACHI\Modular\Support\PhpStorm;

use InterNACHI\Modular\Support\ModuleConfig;
use SimpleXMLElement;

class ProjectImlWriter extends ConfigWriter
{
	public function write(): bool
	{
		$modules_directory = config('app-modules.modules_directory', 'app-modules');
		
		$iml = $this->getNormalizedPluginConfig();
		$source_folders = $iml->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');
		$existing_urls = collect($source_folders)->map(function($node) {
			return (string) $node['url'];
		});
		
		// Now add all missing modules to the config
		$content = $iml->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]')[0];
		$this->module_registry->modules()
			->sortBy('name')
			->each(function(ModuleConfig $module_config) use (&$content, $modules_directory, $existing_urls) {
				$src_url = "file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/src";
				
				if (!$existing_urls->contains($src_url)) {
					$src_node = $content->addChild('sourceFolder');
					$src_node->addAttribute('url', $src_url);
					$src_node->addAttribute('isTestSource', 'false');
					$src_node->addAttribute('packagePrefix', rtrim($module_config->namespaces->first(), '\\'));
				}
				
				$tests_url = "file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/tests";
				if (!$existing_urls->contains($tests_url)) {
					$tests_node = $content->addChild('sourceFolder');
					$tests_node->addAttribute('url', $tests_url);
					$tests_node->addAttribute('isTestSource', 'true');
					$tests_node->addAttribute('packagePrefix', rtrim($module_config->namespaces->first(), '\\').'\\Tests');
				}
			});
		
		return false !== file_put_contents($this->config_path, $this->formatXml($iml));
	}
	
	protected function getNormalizedPluginConfig(): SimpleXMLElement
	{
		$config = simplexml_load_string(file_get_contents($this->config_path));
		
		// Ensure that <component name="NewModuleRootManager"> exists
		$component = $config->xpath('//component[@name="NewModuleRootManager"]');
		if (empty($component)) {
			$component = $config->addChild('component');
			$component->addAttribute('name', 'NewModuleRootManager');
		} else {
			$component = $component[0];
		}
		
		// Ensure that <content url="file://$MODULE_DIR$"> exists
		$content = $component->xpath('//content[@url="file://$MODULE_DIR$"]');
		if (empty($content)) {
			$content = $component->addChild('content');
			$content->addAttribute('url', 'file://$MODULE_DIR$');
		}
		
		return $config;
	}
}
