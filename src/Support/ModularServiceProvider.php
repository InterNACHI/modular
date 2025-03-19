<?php

namespace InterNACHI\Modular\Support;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModulesCache;
use InterNACHI\Modular\Console\Commands\ModulesClear;
use InterNACHI\Modular\Console\Commands\ModulesList;
use InterNACHI\Modular\Console\Commands\ModulesSync;
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
		$this->mergeConfigFrom("{$this->base_dir}/config.php", 'app-modules');
		
		$this->app->singleton(ModuleRegistry::class, function() {
			return new ModuleRegistry(
				$this->getModulesBasePath(),
				$this->app->bootstrapPath('cache/modules.php') // FIXME
			);
		});
		
		$this->app->singleton(FinderFactory::class, function() {
			return new FinderFactory($this->getModulesBasePath());
		});
		
		$this->app->singleton(AutodiscoveryHelper::class, function($app) {
			return new AutodiscoveryHelper(
				$app->make(FinderFactory::class),
				$app->make(Filesystem::class),
				$this->app->bootstrapPath('cache/app-modules.php')
			);
		});
		
		$this->app->singleton(MakeMigration::class, function($app) {
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
		
		$this->bootRoutes();
		$this->bootViews();
		$this->bootBladeComponents();
		$this->bootTranslations();
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
			"{$this->base_dir}/config.php" => $this->app->configPath('app-modules.php'),
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
	
	protected function bootRoutes(): void
	{
		if (! $this->app->routesAreCached()) {
			$this->autodiscover()->routes();
		}
	}
	
	protected function bootViews(): void
	{
		$this->callAfterResolving('view', function(ViewFactory $factory) {
			$this->autodiscover()->views($factory);
		});
	}
	
	protected function bootBladeComponents(): void
	{
		$this->callAfterResolving(BladeCompiler::class, function(BladeCompiler $blade) {
			$this->autodiscover()->blade($blade);
		});
	}
	
	protected function bootTranslations(): void
	{
		$this->callAfterResolving('translator', function(TranslatorContract $translator) {
			if ($translator instanceof Translator) {
				$this->autodiscover()->translations($translator);
			}
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
