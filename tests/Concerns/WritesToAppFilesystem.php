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
		$destination = $this->getBasePath().$this->normalizeDirectorySeparators("{$destination}");
		
		$stubs_directory = str_replace('\\', '/', dirname(__DIR__, 1)).'/stubs';
		
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
				$this->base_path = str_replace('\\', '/', sys_get_temp_dir()).'/'.md5(microtime())
			);
		}
		
		return $this->base_path;
	}
	
	protected function getModulePath(string $module_name, string $path = '/', string $modules_root = 'app-modules'): string
	{
		return $this->getBasePath()
			.'/'
			.$modules_root
			.'/'
			.$module_name
			.$this->normalizeDirectorySeparators($path);
	}
	
	protected function normalizeDirectorySeparators(string $path): string
	{
		if (($path = trim($path, '/')) && (substr($path, 1, 1) !== ':')) {
			$path = '/'.$path;
		}
		
		return $path;
	}
}
