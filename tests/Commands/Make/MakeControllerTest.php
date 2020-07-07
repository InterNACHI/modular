<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeController;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;

class MakeControllerTest extends TestCase
{
	use WritesToAppFilesystem;
	
	public function test_it_scaffolds_a_controller_in_the_module() : void
	{
		$controller_name = 'TestController';
		$module_name = 'test-module';
		
		$this->artisan(MakeModule::class, [
			'name' => $module_name,
			'--accept-default-namespace' => true,
		]);
		
		$this->artisan(MakeController::class, [
			'name' => $controller_name,
			'--module' => $module_name,
		]);
		
		$controller_path = $this->getModulePath($module_name, 'src/Http/Controllers/TestController.php');
		
		$this->assertFileExists($controller_path);
		
		$contents = $this->filesystem()->get($controller_path);
		
		$this->assertStringContainsString('namespace Modules\TestModule\Http\Controllers', $contents);
		$this->assertStringContainsString('use App\Http\Controllers\Controller', $contents);
		$this->assertStringContainsString('class TestController extends Controller', $contents);
	}
}
