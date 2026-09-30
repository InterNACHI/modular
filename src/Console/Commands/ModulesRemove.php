<?php

namespace InterNACHI\Modular\Console\Commands;

use Composer\Factory;
use Composer\Json\JsonFile;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;

class ModulesRemove extends Command
{
	protected $signature = 'modules:remove
		{name : The name of the module}
		{--force : Remove the module without asking for confirmation}';

	protected $description = 'Delete a module and remove it from the application composer.json file';

	public function __construct(
		protected Filesystem $filesystem,
		protected ModuleRegistry $module_registry
	) {
		parent::__construct();
	}

	public function handle(): int
	{
		$module = $this->module_registry->module($this->argument('name'));

		if (null === $module) {
			$this->error("Module '{$this->argument('name')}' does not exist.");
			return 1;
		}

		if (! $this->option('force') && ! $this->confirm("This will permanently delete {$module->base_path}. Continue?", false)) {
			$this->line('Module was not removed.');
			return 1;
		}

		$composer_name = $this->composerName($module);

		$this->filesystem->deleteDirectory($module->base_path);
		$this->line(" - Deleted <info>{$module->base_path}</info>");

		if ($composer_name) {
			$this->removeFromCoreComposerConfig($composer_name);
		}

		$this->call(ModulesClear::class);

		$this->module_registry->reload();

		if ($composer_name) {
			$this->newLine();
			$this->line("Please run <comment>composer update {$composer_name}</comment>");
			$this->newLine();
		}

		return 0;
	}

	protected function composerName(ModuleConfig $module): ?string
	{
		$composer_file = $module->path('composer.json');

		if (! $this->filesystem->isFile($composer_file)) {
			return null;
		}

		return json_decode($this->filesystem->get($composer_file), true)['name'] ?? null;
	}

	protected function removeFromCoreComposerConfig(string $composer_name): void
	{
		// We're going to move into the Laravel base directory while
		// we're updating the composer file so that we're sure we update
		// the correct composer.json file (we'll restore CWD at the end)
		$original_working_dir = getcwd();
		chdir($this->laravel->basePath());

		$json_file = new JsonFile(Factory::getComposerFile());
		$definition = $json_file->read();

		$has_changes = false;

		foreach (['require', 'require-dev'] as $key) {
			if (isset($definition[$key][$composer_name])) {
				unset($definition[$key][$composer_name]);
				$this->line(" - Removed <info>{$composer_name}</info> from {$key}");
				$has_changes = true;
			}
		}

		if ($has_changes) {
			$json_file->write($definition);
			$this->line(" - Wrote to <info>{$json_file->getPath()}</info>");
		}

		chdir($original_working_dir);
	}
}
