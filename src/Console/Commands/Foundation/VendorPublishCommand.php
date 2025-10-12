<?php

declare(strict_types=1);

namespace InterNACHI\Modular\Console\Commands\Foundation;

use Illuminate\Foundation\Events\VendorTagPublished;
use InterNACHI\Modular\Console\Commands\Modularize;
use InterNACHI\Modular\Support\ModuleConfig;

class VendorPublishCommand extends \Illuminate\Foundation\Console\VendorPublishCommand
{
	use Modularize;

	protected function publishTag($tag)
	{
		$pathsToPublish = $this->pathsToPublish($tag);

		if ($publishing = count($pathsToPublish) > 0) {
			$this->components->info(sprintf(
				'Publishing %sassets',
				$tag ? "[$tag] " : '',
			));
		}

		$module = $this->module();

		foreach ($pathsToPublish as $from => &$to) {
			if ($module) {
				$this->updateTargetPath($module, $to);
			}

			$this->publishItem($from, $to);
		}

		if ($publishing === false) {
			$this->components->info('No publishable resources for tag ['.$tag.'].');
		} else {
			$this->laravel['events']->dispatch(new VendorTagPublished($tag, $pathsToPublish));

			$this->newLine();
		}
	}

	protected function updateTargetPath(ModuleConfig $module, string &$path)
	{
		$base_path = $this->laravel->basePath();
		$module_path = $module->path();

		$path = str_replace($base_path, $module_path, $path);
	}
}
