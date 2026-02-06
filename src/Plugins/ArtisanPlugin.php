<?php

namespace InterNACHI\Modular\Plugins;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\FinderFactory;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleFileInfo;
use InterNACHI\Modular\Support\ModuleRegistry;
use ReflectionClass;

class ArtisanPlugin extends Plugin
{
	public static function boot(Closure $handler, Application $app): void
	{
		Artisan::starting(fn($artisan) => $handler(static::class, ['artisan' => $artisan]));
	}
	
	public function __construct(
		protected Artisan $artisan,
		protected ModuleRegistry $registry,
	) {
	}
	
	public function discover(FinderFactory $finders): iterable
	{
		return $finders
			->commandFileFinder()
			->withModuleInfo()
			->values()
			->map(fn(ModuleFileInfo $file) => $file->fullyQualifiedClassName())
			->filter($this->isInstantiableCommand(...));
	}
	
	public function handle(Collection $data): void
	{
		$data->each(fn(string $fqcn) => $this->artisan->resolve($fqcn));
		
		$this->registerNamespacesInTinker();
	}
	
	protected function registerNamespacesInTinker(): void
	{
		if (! class_exists('Laravel\\Tinker\\TinkerServiceProvider')) {
			return;
		}
		
		$namespaces = $this->registry
			->modules()
			->flatMap(fn(ModuleConfig $config) => $config->namespaces)
			->reject(fn($ns) => Str::endsWith($ns, ['Tests\\', 'Database\\Factories\\', 'Database\\Seeders\\']))
			->values()
			->all();
		
		Config::set('tinker.alias', array_merge($namespaces, Config::get('tinker.alias', [])));
	}
	
	protected function isInstantiableCommand($command): bool
	{
		return is_subclass_of($command, Command::class)
			&& ! (new ReflectionClass($command))->isAbstract();
	}
}
