<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Console\Commands\ModulesClear;
use InterNACHI\Modular\Console\Commands\ModulesList;
use InterNACHI\Modular\Console\Commands\ModulesSync;
use InterNACHI\Modular\PluginRegistry;
use InterNACHI\Modular\Support\Autodiscovery\ArtisanPlugin;
use InterNACHI\Modular\Support\Autodiscovery\BladePlugin;
use InterNACHI\Modular\Support\Autodiscovery\EventsPlugin;
use InterNACHI\Modular\Support\Autodiscovery\GatePlugin;
use InterNACHI\Modular\Support\Autodiscovery\LivewirePlugin;
use InterNACHI\Modular\Support\Autodiscovery\MigratorPlugin;
use InterNACHI\Modular\Support\Autodiscovery\ModulesPlugin;
use InterNACHI\Modular\Support\Autodiscovery\RoutesPlugin;
use InterNACHI\Modular\Support\Autodiscovery\TranslatorPlugin;
use InterNACHI\Modular\Support\Autodiscovery\ViewPlugin;
use Livewire\LivewireManager;

class ModularServiceProvider extends ServiceProvider
{
	protected string $base_dir;
	
	protected ?string $modules_path = null;
	
	public function __construct($app)
	{
		parent::__construct($app);
		
		$this->base_dir = str_replace('\\', '/', dirname(__DIR__, 2));
	}
	
	public function register(): void
	{
		$this->mergeConfigFrom("{$this->base_dir}/config/app-modules.php", 'app-modules');
		
		// $this->app->singleton(ModulesPlugin::class);
		
		$this->app->singleton(ModuleRegistry::class, fn(Application $app) => new ModuleRegistry(
			modules_path: $this->getModulesBasePath(),
			modules_loader: static function() use ($app) {
				return $app->make(PluginHandler::class)->handle(ModulesPlugin::class);
			},
		));
		
		$this->app->singleton(FinderFactory::class, fn() => new FinderFactory($this->getModulesBasePath()));
		
		$this->app->singleton(Cache::class, fn(Application $app) => new Cache(
			$app->make(Filesystem::class),
			$this->app->bootstrapPath('cache/app-modules.php')
		));
		
		$this->app->singleton(PluginDataRepository::class, fn(Application $app) => new PluginDataRepository(
			data: $app->make(Cache::class)->read(),
			registry: $app->make(PluginRegistry::class),
			finders: $app->make(FinderFactory::class),
		));
		
		$this->app->singleton(PluginRegistry::class);
		$this->app->singleton(PluginHandler::class);
		
		// Because of the way migration dependencies are registered (as strings rather than class names),
		// we need to wire up our dependencies manually for migration-specific features 
		$this->app->singleton(MakeMigration::class, fn(Application $app) => new MigrateMakeCommand($app['migration.creator'], $app['composer']));
		$this->app->singleton(MigratorPlugin::class, fn(Application $app) => new MigratorPlugin($app->make('migrator')));
		
		$this->registerEloquentFactories();
		$this->registerDefaultPlugins();
		
		$this->app->booting(fn() => $this->app->make(PluginHandler::class)->boot($this->app));
	}
	
	public function boot(): void
	{
		$this->publishes([
			"{$this->base_dir}/config/app-modules.php" => $this->app->configPath('app-modules.php'),
		], 'modular-config');
		
		if ($this->app->runningInConsole()) {
			$this->commands([
				MakeModule::class,
				ModulesCache::class,
				ModulesClear::class,
				ModulesSync::class,
				ModulesList::class,
			]);
		}
	}
	
	protected function registerDefaultPlugins(): void
	{
		$registry = $this->app->make(PluginRegistry::class);
		
		$registry->add(
			ArtisanPlugin::class,
			BladePlugin::class,
			EventsPlugin::class,
			GatePlugin::class,
			LivewirePlugin::class,
			MigratorPlugin::class,
			ModulesPlugin::class,
			RoutesPlugin::class,
			TranslatorPlugin::class,
			ViewPlugin::class,
		);
		
		if (class_exists(LivewireManager::class)) {
			$registry->add(LivewirePlugin::class);
		}
	}
	
	protected function registerEloquentFactories(): void
	{
		$helper = new DatabaseFactoryHelper($this->app->make(ModuleRegistry::class));
		
		EloquentFactory::guessModelNamesUsing($helper->modelNameResolver());
		EloquentFactory::guessFactoryNamesUsing($helper->factoryNameResolver());
	}
	
	protected function getModulesBasePath(): string
	{
		if (null === $this->modules_path) {
			$directory_name = $this->app->make('config')->get('app-modules.modules_directory', 'app-modules');
			$this->modules_path = str_replace('\\', '/', $this->app->basePath($directory_name));
		}
		
		return $this->modules_path;
	}
}
