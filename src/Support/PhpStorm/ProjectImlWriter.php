<?php

namespace InterNACHI\Modular\Support\PhpStorm;

use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleConfig;
use SimpleXMLElement;

class ProjectImlWriter extends ConfigWriter
{
	public function write() : bool
	{
		$modules_directory = config('app-modules.modules_directory', 'app-modules');
		
		$patterns = $this->module_registry->modules()
			->sortBy('name')
			->map(function(ModuleConfig $module_config) use ($modules_directory) {
				return [
					"file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/src",
					"file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/tests",
				];
			})
			->flatten()
			->toArray();
		
		$iml = $this->getNormalizedPluginConfig();
		$source_folders = $iml->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]//sourceFolder');
		
		// Clean up template paths to prevent duplicates
		foreach ($source_folders as $key => $existing) {
			if (Str::of($existing['url'])->is($patterns)) {
				unset($source_folders[$key][0]);
			}
		}
		
		// Now add all modules to the config
		$content = $iml->xpath('//component[@name="NewModuleRootManager"]//content[@url="file://$MODULE_DIR$"]')[0];
		$this->module_registry->modules()
			->sortBy('name')
			->each(function(ModuleConfig $module_config) use (&$content, $modules_directory) {
				$src_node = $content->addChild('sourceFolder');
				$src_node->addAttribute('url', "file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/src");
				$src_node->addAttribute('isTestSource', 'false');
				$src_node->addAttribute('packagePrefix', rtrim($module_config->namespaces->first(), '\\'));
				
				$tests_node = $content->addChild('sourceFolder');
				$tests_node->addAttribute('url', "file://\$MODULE_DIR\$/{$modules_directory}/{$module_config->name}/tests");
				$tests_node->addAttribute('isTestSource', 'true');
				$tests_node->addAttribute('packagePrefix', rtrim($module_config->namespaces->first(), '\\').'\\Tests');
			});
		
		return false !== file_put_contents($this->config_path, $this->formatXml($iml));
	}
	
	protected function getNormalizedPluginConfig() : SimpleXMLElement
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
