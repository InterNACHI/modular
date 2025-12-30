<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Console\Application as Artisan;
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
use InterNACHI\Modular\Support\Autodiscovery\ArtisanPlugin;
use InterNACHI\Modular\Support\Autodiscovery\BladePlugin;
use InterNACHI\Modular\Support\Autodiscovery\EventsPlugin;
use InterNACHI\Modular\Support\Autodiscovery\GatePlugin;
use InterNACHI\Modular\Support\Autodiscovery\LivewirePlugin;
use InterNACHI\Modular\Support\Autodiscovery\MigratorPlugin;
use InterNACHI\Modular\Support\Autodiscovery\PluginRegistry;
use InterNACHI\Modular\Support\Autodiscovery\RoutesPlugin;
use InterNACHI\Modular\Support\Autodiscovery\TranslatorPlugin;
use InterNACHI\Modular\Support\Autodiscovery\ViewPlugin;
use Livewire\LivewireManager;

class ModularServiceProvider extends ServiceProvider
{
	protected ?AutodiscoveryHelper $autodiscovery_helper = null;
	
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
		
		$this->app->singleton(ModuleRegistry::class, function(Application $app) {
			return new ModuleRegistry(
				$this->getModulesBasePath(),
				$app->make(AutodiscoveryHelper::class),
			);
		});
		
		$this->app->singleton(FinderFactory::class, function() {
			return new FinderFactory($this->getModulesBasePath());
		});
		
		$this->app->singleton(AutodiscoveryHelper::class, function(Application $app) {
			return new AutodiscoveryHelper(
				$app->make(FinderFactory::class),
				$app->make(Filesystem::class),
				$app,
				$this->app->bootstrapPath('cache/app-modules.php')
			);
		});
		
		$this->app->singleton(MakeMigration::class, function(Application $app) {
			return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
		});
		
		$this->registerEloquentFactories();
		
		PluginRegistry::register(
			RoutesPlugin::class,
			TranslatorPlugin::class,
			ViewPlugin::class,
			BladePlugin::class,
			EventsPlugin::class,
			MigratorPlugin::class,
			GatePlugin::class,
		);
		
		$this->app->booting($this->bootPlugins(...));
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
	
	protected function bootPlugins(): void
	{
		$plugins = PluginRegistry::instance()->all();
		
		// First register all plugins with the auto-discovery helper
		foreach ($plugins as $class) {
			$this->autodiscover()->register($class);
		}
		
		// Then boot all plugins that have annotations
		$this->autodiscover()->bootPlugins();
		
		// Finally, handle some special plugin cases
		$this->autodiscover()->handleIf(RoutesPlugin::class, condition: ! $this->app->routesAreCached());
		$this->autodiscover()->handleIf(LivewirePlugin::class, condition: class_exists(LivewireManager::class));
		Artisan::starting(fn($artisan) => $this->autodiscover()->handle(ArtisanPlugin::class, $artisan));
	}
	
	protected function autodiscover(): AutodiscoveryHelper
	{
		return $this->autodiscovery_helper ??= $this->app->make(AutodiscoveryHelper::class);
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
