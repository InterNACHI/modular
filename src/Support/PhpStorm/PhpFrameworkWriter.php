<?php

namespace InterNACHI\Modular\Support\PhpStorm;

use InterNACHI\Modular\Console\Commands\Modularize;
use InterNACHI\Modular\Support\ModuleConfig;

class PhpFrameworkWriter extends ConfigWriter
{
	use Modularize;

	public function write(): bool
	{
		$config = simplexml_load_string(file_get_contents($this->config_path));
		if (empty($config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path'))) {
			return true;
		}

		$namespace = config('app-modules.modules_namespace', 'Modules');
		$vendor = config('app-modules.modules_vendor') ?? $this->kebabCase($namespace);
		$module_paths = $this->module_registry->modules()
			->map(function(ModuleConfig $module) use (&$config, $vendor) {
				return '$PROJECT_DIR$/vendor/'.$vendor.'/'.$module->name;
			});

		$include_paths = $config->xpath('//component[@name="PhpIncludePathManager"]//include_path//path');

		foreach ($include_paths as $key => $existing) {
			if ($module_paths->contains((string) $existing['value'])) {
				unset($include_paths[$key][0]);
			}
		}

		return false !== file_put_contents($this->config_path, $this->formatXml($config));
	}
}
