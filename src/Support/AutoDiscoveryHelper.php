<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Support\Str;
use Composer\InstalledVersions;
use Illuminate\Filesystem\Filesystem;

class AutoDiscoveryHelper
{
	protected string $base_path;
	protected array $installed_packages = [];
	
	public function __construct(
		protected ModuleRegistry $module_registry,
		protected Filesystem $filesystem
	) {
		$this->base_path = $module_registry->getModulesPath();
		$this->installed_packages = InstalledVersions::getInstalledPackages();
	}

    private function dirs(string $postfix): array|string
    {
        $directories = [];
        foreach ($this->installed_packages as $installed_package) {
            if (Str::startsWith($installed_package, config('app-modules.modules_directory'))) {
                $directories[] = base_path($installed_package.'/'.$postfix);
            }
        }

        return $directories;
    }

    public function commandFileFinder(): FinderCollection
    {
        return FinderCollection::forFiles()
            ->name('*.php')
            ->inOrEmpty($this->dirs('src/Console/Commands'));
    }

    public function factoryDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->depth(0)
            ->name('factories')
            ->inOrEmpty($this->dirs('database/'));
    }

    public function migrationDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->depth(0)
            ->name('migrations')
            ->inOrEmpty($this->dirs('database/'));
    }

    public function modelFileFinder(): FinderCollection
    {
        return FinderCollection::forFiles()
            ->name('*.php')
            ->inOrEmpty($this->dirs('src/Models'));
    }

    public function bladeComponentFileFinder(): FinderCollection
    {
        return FinderCollection::forFiles()
            ->name('*.php')
            ->inOrEmpty($this->dirs('src/View/Components'));
    }

    public function bladeComponentDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->name('Components')
            ->inOrEmpty($this->dirs('src/View'));
    }

    public function routeFileFinder(): FinderCollection
    {
        return FinderCollection::forFiles()
            ->depth(0)
            ->name('*.php')
            ->sortByName()
            ->inOrEmpty($this->dirs('routes'));
    }

    public function viewDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->depth(0)
            ->name('views')
            ->inOrEmpty($this->dirs('resources/'));
    }

    public function langDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->depth(0)
            ->name('lang')
            ->inOrEmpty($this->dirs('resources/'));
    }

    public function listenerDirectoryFinder(): FinderCollection
    {
        return FinderCollection::forDirectories()
            ->name('Listeners')
            ->inOrEmpty($this->dirs('src'));
    }

    public function livewireComponentFileFinder(): FinderCollection
    {
        $directory = 'src';

        if (str_contains(config('livewire.class_namespace'), '\\Http\\')) {
            $directory .= '/Http';
        }

        $directory .= '/Livewire';

        return FinderCollection::forFiles()
            ->name('*.php')
            ->inOrEmpty($this->dirs($directory));
    }
}
