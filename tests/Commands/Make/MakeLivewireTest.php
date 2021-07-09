<?php

namespace InterNACHI\Modular\Tests\Commands\Make;

use InterNACHI\Modular\Console\Commands\Make\MakeComponent;
use InterNACHI\Modular\Console\Commands\Make\MakeLivewire;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModularizedCommandsServiceProvider;
use InterNACHI\Modular\Support\ModularServiceProvider;
use InterNACHI\Modular\Tests\Concerns\TestsMakeCommands;
use InterNACHI\Modular\Tests\Concerns\WritesToAppFilesystem;
use InterNACHI\Modular\Tests\TestCase;
use Livewire\Livewire;
use Livewire\LivewireManager;
use Livewire\LivewireServiceProvider;

class MakeLivewireTest extends TestCase
{
    use WritesToAppFilesystem;
    use TestsMakeCommands;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Livewire::class)) {
            $this->markTestSkipped('Livewire is not installed.');
        }
    }

    public function test_it_scaffolds_a_component_in_the_module_when_module_option_is_set(): void
    {
        $command = MakeLivewire::class;
        $arguments = ['name' => 'TestLivewireComponent'];
        $expected_path = 'src/Http/Livewire/TestLivewireComponent.php';
        $expected_substrings = [
            'namespace Modules\TestModule\Http\Livewire',
            'class TestLivewireComponent',
        ];

        $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);

        $expected_view_path = 'resources/views/livewire/test-livewire-component.blade.php';
        $this->assertModuleFile($expected_view_path);
    }

    public function test_it_scaffolds_a_component_with_nested_folders(): void
    {
        $command = MakeLivewire::class;
        $arguments = ['name' => 'test.my-component/TestLivewireComponent'];
        $expected_path = 'src/Http/Livewire/Test/MyComponent/TestLivewireComponent.php';
        $expected_substrings = [
            'namespace Modules\TestModule\Http\Livewire\Test\MyComponent',
            'class TestLivewireComponent',
        ];

        $this->assertModuleCommandResults($command, $arguments, $expected_path, $expected_substrings);

        $expected_view_path = 'resources/views/livewire/test/my-component/test-livewire-component.blade.php';
        $this->assertModuleFile($expected_view_path);
    }

    public function test_it_scaffolds_a_component_in_the_app_when_module_option_is_missing(): void
    {
        $command = MakeLivewire::class;
        $arguments = ['name' => 'TestLivewireComponent'];
        $expected_path = 'app/Http/Livewire/TestLivewireComponent.php';
        $expected_substrings = [
            'namespace App\Http\Livewire',
            'class TestLivewireComponent',
        ];

        $this->assertBaseCommandResults($command, $arguments, $expected_path, $expected_substrings);

        $expected_view_path = 'resources/views/livewire/test-livewire-component.blade.php';
        $this->assertBaseFile($expected_view_path);
    }

    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [LivewireServiceProvider::class]);
    }

    protected function getPackageAliases($app)
    {
        return array_merge(parent::getPackageAliases($app), ['Livewire' => LivewireManager::class]);
    }
}
