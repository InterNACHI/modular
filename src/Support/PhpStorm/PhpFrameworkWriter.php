<?php

namespace InterNACHI\Modular\Support\PhpStorm;

use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleConfig;
use SimpleXMLElement;

class PhpFrameworkWriter extends ConfigWriter
{
	public function write(): bool
	{
		$config = $this->getNormalizedPluginConfig();
		
		$namespace = config('app-modules.modules_namespace', 'Modules');
		$vendor = config('app-modules.modules_vendor') ?? Str::kebab($namespace);
		$module_paths = $this->module_registry->modules()
			->map(function(ModuleConfig $module) use (&$config, $vendor) {
				return '$PROJECT_DIR$/vendor/'.$vendor.'/'.$module->name;
			});
		
		// Remove modules from include_path
		if (! empty($config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path'))) {
			$include_paths = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path');
			foreach ($include_paths as $key => $existing) {
				if ($module_paths->contains((string) $existing['value'])) {
					unset($include_paths[$key][0]);
				}
			}
		}
		
		// Add modules to exclude_path
		$exclude_paths = $config->xpath('//component[@name="PhpIncludePathManager"]//exclude_path//path');
		$existing_values = collect($exclude_paths)->map(function($node) {
			return (string) $node['value'];
		});
		
		// Now add all missing modules to the config
		$content = $config->xpath('//component[@name="PhpIncludePathManager"]//exclude_path')[0];
		$module_paths->each(function(string $module_path) use (&$content, $existing_values) {
			if ($existing_values->contains($module_path)) {
				return;
			}
			
			$path_node = $content->addChild('path');
			$path_node->addAttribute('value', $module_path);
		});
		
		return false !== file_put_contents($this->config_path, $this->formatXml($config));
	}
	
	protected function getNormalizedPluginConfig(): SimpleXMLElement
	{
		$config = simplexml_load_string(file_get_contents($this->config_path));
		
		// Ensure that <component name="PhpIncludePathManager"> exists
		$component = $config->xpath('//component[@name="PhpIncludePathManager"]');
		if (empty($component)) {
			$component = $config->addChild('component');
			$component->addAttribute('name', 'PhpIncludePathManager');
		} else {
			$component = $component[0];
		}
		
		// Ensure that <exclude_path> exists
		$content = $component->xpath('//exclude_path');
		if (empty($content)) {
			$component->addChild('exclude_path');
		}
		
		return $config;
	}
}
