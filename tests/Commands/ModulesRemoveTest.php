<?php

namespace InterNACHI\Modular\Tests\Commands;

use InterNACHI\Modular\Console\Commands\ModulesRemove;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

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

	protected function appComposerRequirements(): array
	{
		$composer_file = $this->getApplicationBasePath().'/composer.json';

		return json_decode($this->filesystem()->get($composer_file), true)['require'] ?? [];
	}
}
