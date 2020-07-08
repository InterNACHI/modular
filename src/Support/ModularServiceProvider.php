<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModuleCache;
use InterNACHI\Modular\Console\Commands\ModuleClear;
use InterNACHI\Modular\Console\Commands\ModuleSync;
use InterNACHI\Modular\Console\Commands\ModuleList;
use Symfony\Component\Finder\SplFileInfo;

class ModularServiceProvider extends ServiceProvider
{
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $registry;
	
	/**
	 * @var \InterNACHI\Modular\Support\AutoDiscoveryResolver
	 */
	protected $resolver;
	
	/**
	 * This is the base directory of the modular package
	 *
	 * @var string
	 */
	protected $base_dir;
	
	/**
	 * This is the configured modules directory (app-modules/ by default)
	 * 
	 * @var string 
	 */
	protected $modules_path;
	
	public function __construct($app)
	{
		parent::__construct($app);
		
		$this->base_dir = dirname(__DIR__, 2);
	}
	
	public function register(): void
	{
		$this->mergeConfigFrom("{$this->base_dir}/config.php", 'app-modules');
		
		$this->app->singleton(ModuleRegistry::class, function() {
			return new ModuleRegistry(
				$this->getModulesBasePath(),
				$this->app->bootstrapPath('cache/modules.php')
			);
		});
		
		$this->app->singleton(AutoDiscoveryResolver::class, function($app) {
			return new AutoDiscoveryResolver(
				$app->make(ModuleRegistry::class),
				$app->make(Filesystem::class),
				$this->getModulesBasePath()
			);
		});
		
		$this->app->singleton(MakeMigration::class, function($app) {
			return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
		});
		
		// Set up lazy registrations for things that only need to run if we're using
		// that functionality (e.g. we only need to look for and register migrations
		// if we're running the migrator)
		$this->registerLazily(Migrator::class, [$this, 'registerMigrations']);
		$this->registerLazily(EloquentFactory::class, [$this, 'registerFactories']);
		$this->registerLazily(Gate::class, [$this, 'registerPolicies']);
		
		// Look for and register all our commands in the CLI context
		Artisan::starting(Closure::fromCallable([$this, 'registerCommands']));
	}
	
	public function boot(): void
	{
		$this->publishVendorFiles();
		$this->bootPackageCommands();
		
		$this->bootRoutes();
		$this->bootBreadcrumbs();
		$this->bootViews();
	}
	
	protected function registry(): ModuleRegistry
	{
		if (null === $this->registry) {
			$this->registry = $this->app->make(ModuleRegistry::class);
		}
		
		return $this->registry;
	}
	
	protected function resolver(): AutoDiscoveryResolver
	{
		if (null === $this->resolver) {
			$this->resolver = $this->app->make(AutoDiscoveryResolver::class);
		}
		
		return $this->resolver;
	}
	
	protected function publishVendorFiles(): void
	{
		$this->publishes([
			"{$this->base_dir}/config.php" => $this->app->configPath('app-modules.php'),
		], 'modular-config');
	}
	
	protected function bootPackageCommands(): void 
	{
		if (!$this->app->runningInConsole()) {
			return;
		}
		
		$this->commands([
			MakeModule::class,
			ModuleCache::class,
			ModuleClear::class,
			ModuleSync::class,
			ModuleList::class,
		]);
	}
	
	protected function bootRoutes(): void
	{
		if ($this->app->routesAreCached()) {
			return;
		}
		
		$this->resolver()->discoverRoutes(function(SplFileInfo $file) {
			require $file->getRealPath();
		});
	}
	
	protected function bootViews(): void
	{
		$this->callAfterResolving('view', function(Factory $view_factory) {
			$this->resolver()->discoverViewPaths(function(SplFileInfo $directory) use ($view_factory) {
				$view_factory->addNamespace(
					$directory->getBasename(),
					$directory->getRealPath().'/resources/views/'
				);
			});
		});
	}
	
	protected function bootBreadcrumbs(): void
	{
		$class_name = 'DaveJamesMiller\\Breadcrumbs\\BreadcrumbsManager';
		
		if (!class_exists($class_name)) {
			return;
		}
		
		// The breadcrumbs package makes $breadcrumbs available in the scope of breadcrumb
		// files, so we'll do the same for consistency-sake
		$breadcrumbs = $this->app->make($class_name);
		
		$files = glob($this->getModulesBasePath().'/*/routes/breadcrumbs/*.php');
		
		foreach ($files as $file) {
			require_once $file;
		}
	}
	
	protected function registerMigrations(Migrator $migrator): void
	{
		$this->resolver()->discoverMigrations(function(SplFileInfo $path) use ($migrator) {
			$migrator->path($path->getRealPath());
		});
	}
	
	protected function registerFactories(EloquentFactory $factory): void
	{
		$this->resolver()->discoverFactories(function(SplFileInfo $path) use ($factory) {
			$factory->load($path->getRealPath());
		});
	}
	
	protected function registerPolicies(Gate $gate): void
	{
		$this->resolver()->discoverPolicies(function($class, $policy) use ($gate) {
			$gate->policy($class, $policy);
		});
	}
	
	protected function registerCommands(Artisan $artisan): void
	{
		$this->resolver()->discoverCommands(function($command) use ($artisan) {
			$artisan->resolve($command);
		});
	}
	
	protected function registerLazily(string $class_name, callable $callback): self
	{
		$this->app->resolving($class_name, Closure::fromCallable($callback));
		
		return $this;
	}
	
	protected function getModulesBasePath() : string
	{
		if (null === $this->modules_path) {
			$directory_name = $this->app->make('config')->get('app-modules.modules_directory', 'app-modules');
			$this->modules_path = $this->app->basePath($directory_name);
		}
		
		return $this->modules_path;
	}
}
