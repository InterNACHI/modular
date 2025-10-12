<?php

declare(strict_types=1);

namespace InterNACHI\Modular\Tests\Commands\Foundation;

use InterNACHI\Modular\Console\Commands\Foundation\VendorPublishCommand;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class VendorPublishCommandTest extends TestCase
{
	use WritesToAppFilesystem;
	use TestsMakeCommands;

	public function test_it_overrides_the_default_command(): void
	{
		$this->requiresLaravelVersion('9.2.0');

		$this->artisan('vendor:publish', ['--help' => true])
			->expectsOutputToContain('--module')
			->assertExitCode(0);
	}

	public function test_it_published_to_correct_module()
	{
		$this->requiresLaravelVersion('9.2.0');

		$command = VendorPublishCommand::class;
		$arguments = ['--tag' => 'laravel-mail'];
		$expected_path = 'resources/views/vendor/mail/html/layout.blade.php';

		$this->assertModuleCommandResults($command, $arguments, $expected_path, []);
	}
}
