<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use InterNACHI\Modular\Console\Commands\Make\MakeMigration;
use InterNACHI\Modular\Console\Commands\Make\MakeModule;
use InterNACHI\Modular\Console\Commands\ModuleCache;
use InterNACHI\Modular\Console\Commands\ModuleClear;
use InterNACHI\Modular\Console\Commands\ModuleSync;
use InterNACHI\Modular\Console\Commands\ModuleList;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class ModularServiceProvider extends ServiceProvider
{
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $registry;
	
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
		
		$this->app->singleton(MakeMigration::class, function($app) {
			return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
		});
		
		if ($this->modulesBasePathExists()) {
			// Set up lazy registrations for things that only need to run if we're using
			// that functionality (e.g. we only need to look for and register migrations
			// if we're running the migrator)
			$this->registerLazily(Migrator::class, [$this, 'registerMigrations']);
			$this->registerLazily(EloquentFactory::class, [$this, 'registerFactories']);
			$this->registerLazily(Gate::class, [$this, 'registerPolicies']);
			
			// Look for and register all our commands in the CLI context
			Artisan::starting(Closure::fromCallable([$this, 'registerCommands']));
		}
	}
	
	public function boot(): void
	{
		$this->publishVendorFiles();
		$this->bootPackageCommands();
		
		if ($this->modulesBasePathExists()) {
			$this->bootRoutes();
			$this->bootBreadcrumbs();
			$this->bootViews();
		}
	}
	
	protected function registry(): ModuleRegistry
	{
		if (null === $this->registry) {
			$this->registry = $this->app->make(ModuleRegistry::class);
		}
		
		return $this->registry;
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
		
		FinderCollection::forFiles()
			->depth(2)
			->path('routes/')
			->name('*.php')
			->in($this->getModulesBasePath())
			->each(function(SplFileInfo $file) {
				require $file->getRealPath();
			});
	}
	
	protected function bootViews(): void
	{
		$this->callAfterResolving('view', function(Factory $view_factory) {
			FinderCollection::forDirectories()
				->depth(0)
				->in($this->getModulesBasePath())
				->each(function(SplFileInfo $directory) use ($view_factory) {
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
		FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('migrations')
			->in($this->getModulesBasePath())
			->each(function(SplFileInfo $path) use ($migrator) {
				$migrator->path($path->getRealPath());
			});
	}
	
	protected function registerFactories(EloquentFactory $factory): void
	{
		FinderCollection::forDirectories()
			->depth('== 2')
			->path('database/')
			->name('factories')
			->in($this->getModulesBasePath())
			->each(function(SplFileInfo $path) use ($factory) {
				$factory->load($path->getRealPath());
			});
	}
	
	protected function registerPolicies(Gate $gate): void
	{
		FinderCollection::forFiles()
			->depth('> 2')
			->path('src/Models')
			->name('*.php')
			->in($this->getModulesBasePath())
			->each(function(SplFileInfo $file) use ($gate) {
				if (!$module = $this->registry()->moduleForPath($file->getPath())) {
					throw new RuntimeException("Unable to determine module for '{$file->getPath()}'");
				}
				
				$fully_qualified_model = $this->pathToFullyQualifiedClassName($file->getPathname(), $module);
				
				// First, check for a policy that maps to the full namespace of the model
				// i.e. Models/Foo/Bar -> Policies/Foo/BarPolicy
				$namespaced_model = Str::after($fully_qualified_model, 'Models\\');
				$namespaced_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$namespaced_model.'Policy';
				if (class_exists($namespaced_policy)) {
					$gate->policy($fully_qualified_model, $namespaced_policy);
					return;
				}
				
				// If that doesn't match, try the simple mapping as well
				// i.e. Models/Foo/Bar -> Policies/BarPolicy
				if (false !== strpos($namespaced_model, '\\')) {
					$simple_model = Str::afterLast($fully_qualified_model, '\\');
					$simple_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$simple_model.'Policy';
					
					if (class_exists($simple_policy)) {
						$gate->policy($fully_qualified_model, $simple_policy);
						return;
					}
				}
			});
	}
	
	protected function registerCommands(Artisan $artisan): void
	{
		FinderCollection::forFiles()
			->depth('> 3')
			->path('src/Console/Commands')
			->name('*.php')
			->in($this->getModulesBasePath())
			->each(function(SplFileInfo $file) use ($artisan) {
				if (!$module = $this->registry()->moduleForPath($file->getPath())) {
					throw new RuntimeException("Unable to determine module for '{$file->getPath()}'");
				}
				
				$command = $this->pathToFullyQualifiedClassName($file->getPathname(), $module);
				if ($this->isInstantiableCommand($command)) {
					$artisan->resolve($command);
				}
			});
	}
	
	protected function registerLazily(string $class_name, callable $callback): self
	{
		$this->app->resolving($class_name, Closure::fromCallable($callback));
		
		return $this;
	}
	
	protected function pathToFullyQualifiedClassName($path, ModuleConfig $module_config): string
	{
		foreach ($module_config->namespaces as $namespace_path => $namespace) {
			if (0 === strpos($path, $namespace_path)) {
				$relative_path = Str::after($path, $namespace_path);
				return $namespace.$this->formatPathAsNamespace($relative_path);
			}
		}
		
		throw new RuntimeException("Unable to infer qualified class name for '{$path}'");
	}
	
	protected function getModulesBasePath() : string
	{
		if (null === $this->modules_path) {
			$directory_name = $this->app->make('config')->get('app-modules.modules_directory', 'app-modules');
			$this->modules_path = $this->app->basePath($directory_name);
		}
		
		return $this->modules_path;
	}
	
	protected function modulesBasePathExists() : bool
	{
		return $this->app->make(Filesystem::class)
			->isDirectory($this->getModulesBasePath());
	}
	
	protected function formatPathAsNamespace(string $path) : string
	{
		$path = trim($path, DIRECTORY_SEPARATOR);
		
		$replacements = [
			'/' => '\\',
			'.php' => '',
		];
		
		return str_replace(
			array_keys($replacements),
			array_values($replacements),
			$path
		);
	}
	
	protected function isInstantiableCommand($command): bool
	{
		return is_subclass_of($command, Command::class)
			&& !(new ReflectionClass($command))->isAbstract();
	}
}
