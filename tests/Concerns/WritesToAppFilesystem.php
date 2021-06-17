<?php

namespace InterNACHI\Modular\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;

trait WritesToAppFilesystem
{
	protected $filesystem;
	
	protected $base_path;
	
	protected function filesystem(): Filesystem
	{
		if (null === $this->filesystem) {
			$this->filesystem = new Filesystem();
		}
		
		return $this->filesystem;
	}
	
	protected function resolveApplication()
	{
		$this->base_path = null;
		
		return parent::resolveApplication();
	}
	
	protected function copyStub(string $stub, string $destination): string
	{
		$destination = trim($destination, '/');
		$destination = $this->getBasePath().$this->normalizeDirectorySeparators("/{$destination}");
		
		$stubs_directory = dirname(__FILE__, 2).'/stubs';
		
		$from = $this->normalizeDirectorySeparators("{$stubs_directory}/{$stub}");
		$to = $this->normalizeDirectorySeparators("{$destination}/{$stub}");
		
		$this->filesystem()->ensureDirectoryExists($destination);
		$this->filesystem()->copy($from, $to);
		
		return $to;
	}
	
	protected function getBasePath()
	{
		if (null === $this->base_path) {
			$this->filesystem()->copyDirectory(
				parent::getBasePath(),
				$this->base_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.md5(microtime())
			);
		}
		
		return $this->base_path;
	}
	
	protected function getModulePath(string $module_name, string $path = '/', string $modules_root = 'app-modules'): string
	{
		return $this->getBasePath()
			.DIRECTORY_SEPARATOR
			.$modules_root
			.DIRECTORY_SEPARATOR
			.$module_name
			.$this->normalizeDirectorySeparators($path);
	}
	
	protected function normalizeDirectorySeparators(string $path): string
	{
		$path = trim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
		
		if ('' !== $path) {
			$path = DIRECTORY_SEPARATOR.$path;
		}
		
		return $path;
	}
}
