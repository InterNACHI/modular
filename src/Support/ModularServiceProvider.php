<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Console\Commands\ModulesClear;
use InterNACHI\Modular\Console\Commands\ModulesList;
use InterNACHI\Modular\Console\Commands\ModulesSync;
use InterNACHI\Modular\Support\Autodiscovery\BladePlugin;
use InterNACHI\Modular\Support\Autodiscovery\EventsPlugin;
use InterNACHI\Modular\Support\Autodiscovery\RoutesPlugin;
use InterNACHI\Modular\Support\Autodiscovery\TranslationsPlugin;
use InterNACHI\Modular\Support\Autodiscovery\ViewPlugin;
use Livewire\LivewireManager;

class ModularServiceProvider extends ServiceProvider
{
	protected ?ModuleRegistry $registry = null;
	
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
		
		$this->app->resolving(Migrator::class, fn(Migrator $migrator) => $this->autodiscover()->migrations($migrator));
		$this->app->resolving(Gate::class, fn(Gate $gate) => $this->autodiscover()->policies($gate));
		
		Artisan::starting(function(Artisan $artisan) {
			$this->autodiscover()->commands($artisan);
			$this->registerNamespacesInTinker();
		});
	}
	
	public function boot(): void
	{
		$this->publishVendorFiles();
		$this->bootPackageCommands();
		
		$this->bootPlugins();
		
		if (! $this->app->routesAreCached()) {
			$this->autodiscover()->handle(RoutesPlugin::class);
		}
		
		$this->callAfterResolving('view', $this->autodiscover()->boot(ViewPlugin::class));
		$this->callAfterResolving(BladeCompiler::class, $this->autodiscover()->boot(BladePlugin::class));
		$this->callAfterResolving('translator', $this->autodiscover()->boot(TranslationsPlugin::class));
		
		$this->bootEvents();
		$this->bootLivewireComponents();
	}
	
	protected function registry(): ModuleRegistry
	{
		return $this->registry ??= $this->app->make(ModuleRegistry::class);
	}
	
	protected function autodiscover(): AutodiscoveryHelper
	{
		return $this->autodiscovery_helper ??= $this->app->make(AutodiscoveryHelper::class);
	}
	
	protected function publishVendorFiles(): void
	{
		$this->publishes([
			"{$this->base_dir}/config/app-modules.php" => $this->app->configPath('app-modules.php'),
		], 'modular-config');
	}
	
	protected function bootPackageCommands(): void
	{
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
	
	protected function bootPlugins()
	{
		$this->autodiscover()
			->register(RoutesPlugin::class)
			->register(TranslationsPlugin::class)
			->register(ViewPlugin::class)
			->register(BladePlugin::class)
			->register(EventsPlugin::class);
	}
	
	protected function bootEvents(): void
	{
		$this->callAfterResolving(Dispatcher::class, function(Dispatcher $events) {
			$this->autodiscover()->plugin(EventsPlugin::class)->boot(
				app: $this->app,
				events: $events,
				config: $this->app->make('config'),
			);
		});
	}
	
	protected function bootLivewireComponents(): void
	{
		if (class_exists(LivewireManager::class)) {
			$this->autodiscover()->livewire($this->app->make(LivewireManager::class));
		}
	}
	
	protected function registerEloquentFactories(): void
	{
		$helper = new DatabaseFactoryHelper($this->registry());
		
		EloquentFactory::guessModelNamesUsing($helper->modelNameResolver());
		EloquentFactory::guessFactoryNamesUsing($helper->factoryNameResolver());
	}
	
	protected function registerNamespacesInTinker(): void
	{
		if (! class_exists('Laravel\\Tinker\\TinkerServiceProvider')) {
			return;
		}
		
		$namespaces = $this->registry()
			->modules()
			->flatMap(fn(ModuleConfig $config) => $config->namespaces)
			->reject(fn($ns) => Str::endsWith($ns, ['Tests\\', 'Database\\Factories\\', 'Database\\Seeders\\']))
			->values()
			->all();
		
		Config::set('tinker.alias', array_merge($namespaces, Config::get('tinker.alias', [])));
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
