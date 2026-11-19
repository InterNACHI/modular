<?php

namespace InterNACHI\Modular\Tests\Commands;

use Composer\Json\JsonFile;
use Composer\Util\Platform;
use InterNACHI\Modular\Console\Commands\ModulesRemove;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;
use RuntimeException;

class ModulesRemoveTest extends TestCase
{
	use WritesToAppFilesystem;

	public function test_it_removes_a_module(): void
	{
		$module = $this->makeModule('test-module');

		$this->assertTrue($this->filesystem()->isDirectory($module->base_path));
		$this->assertArrayHasKey('modules/test-module', $this->appComposerRequirements());

		$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])
			->assertExitCode(0);

		$this->assertFalse($this->filesystem()->isDirectory($module->base_path));
		$this->assertArrayNotHasKey('modules/test-module', $this->appComposerRequirements());
		$this->assertNull(Modules::module('test-module'));
	}

	public function test_it_only_removes_the_given_module(): void
	{
		$this->makeModule('test-module');
		$other = $this->makeModule('other-module');

		$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])
			->assertExitCode(0);

		$this->assertTrue($this->filesystem()->isDirectory($other->base_path));
		$this->assertArrayHasKey('modules/other-module', $this->appComposerRequirements());
		$this->assertNotNull(Modules::module('other-module'));
	}

	public function test_it_asks_for_confirmation(): void
	{
		$module = $this->makeModule('test-module');

		$this->artisan(ModulesRemove::class, ['name' => 'test-module'])
			->expectsConfirmation("This will permanently delete {$module->base_path}. Continue?", 'yes')
			->assertExitCode(0);

		$this->assertFalse($this->filesystem()->isDirectory($module->base_path));
	}

	public function test_it_does_not_remove_the_module_if_confirmation_is_declined(): void
	{
		$module = $this->makeModule('test-module');

		$this->artisan(ModulesRemove::class, ['name' => 'test-module'])
			->expectsConfirmation("This will permanently delete {$module->base_path}. Continue?", 'no')
			->assertExitCode(1);

		$this->assertTrue($this->filesystem()->isDirectory($module->base_path));
		$this->assertArrayHasKey('modules/test-module', $this->appComposerRequirements());
	}

	public function test_it_fails_for_unknown_modules(): void
	{
		$this->artisan(ModulesRemove::class, ['name' => 'does-not-exist', '--force' => true])
			->expectsOutput("Module 'does-not-exist' does not exist.")
			->assertExitCode(1);
	}

	public function test_it_removes_a_module_listed_in_require_dev(): void
	{
		$this->makeModule('test-module');

		$json_file = new JsonFile($this->getApplicationBasePath().'/composer.json');
		$definition = $json_file->read();
		unset($definition['require']['modules/test-module']);
		$definition['require-dev'] = ['modules/test-module' => '*'];
		$json_file->write($definition);

		$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])
			->assertExitCode(0);

		// Composer rejects an empty list here ("require-dev": []), so the
		// section has to go away with its last package
		$this->assertArrayNotHasKey('require-dev', $json_file->read());
	}

	public function test_it_removes_a_module_that_has_no_composer_file(): void
	{
		$module = $this->makeModule('test-module');

		$this->filesystem()->delete($module->path('composer.json'));

		$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])
			->assertExitCode(0);

		$this->assertFalse($this->filesystem()->isDirectory($module->base_path));
		$this->assertNull(Modules::module('test-module'));
	}

	public function test_it_removes_a_module_that_has_an_invalid_composer_file(): void
	{
		$module = $this->makeModule('test-module');

		$this->filesystem()->put($module->path('composer.json'), '{ invalid json');

		$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])
			->assertExitCode(0);

		$this->assertFalse($this->filesystem()->isDirectory($module->base_path));
		$this->assertNull(Modules::module('test-module'));
	}

	public function test_it_restores_the_working_directory_if_the_composer_file_cannot_be_read(): void
	{
		$this->makeModule('test-module');

		$original_working_dir = getcwd();
		$working_dir_after_failure = null;

		Platform::putEnv('COMPOSER', 'missing-composer.json');

		try {
			$this->artisan(ModulesRemove::class, ['name' => 'test-module', '--force' => true])->run();
		} catch (RuntimeException) {
			$working_dir_after_failure = getcwd();
		} finally {
			Platform::clearEnv('COMPOSER');
			chdir($original_working_dir);
		}

		$this->assertSame($original_working_dir, $working_dir_after_failure);
	}

	protected function appComposerRequirements(): array
	{
		$composer_file = $this->getApplicationBasePath().'/composer.json';

		return json_decode($this->filesystem()->get($composer_file), true)['require'] ?? [];
	}
}
