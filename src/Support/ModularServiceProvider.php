<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Eloquent\Factory as LegacyEloquentFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
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
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class ModularServiceProvider extends ServiceProvider
{
	/**
	 * @var \InterNACHI\Modular\Support\ModuleRegistry
	 */
	protected $registry;
	
	/**
	 * @var \InterNACHI\Modular\Support\AutoDiscoveryHelper
	 */
	protected $auto_discovery_helper;
	
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
		
		$this->app->singleton(AutoDiscoveryHelper::class, function($app) {
			return new AutoDiscoveryHelper(
				$app->make(ModuleRegistry::class),
				$app->make(Filesystem::class)
			);
		});
		
		$this->app->singleton(MakeMigration::class, function($app) {
			return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
		});
		
		// Set up lazy registrations for things that only need to run if we're using
		// that functionality (e.g. we only need to look for and register migrations
		// if we're running the migrator)
		$this->registerLazily(Migrator::class, [$this, 'registerMigrations']);
		$this->registerLazily(Gate::class, [$this, 'registerPolicies']);
		$this->registerLazily(LegacyEloquentFactory::class, [$this, 'registerLegacyFactories']);
		
		// If we're running Laravel 8 or higher, set up the Eloquent Factory to resolve
		// module factories as well as App factories.
		if (class_exists(EloquentFactory::class)) {
			$this->registerEloquentFactories();
		}
		
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
		$this->bootBladeComponents();
		$this->bootTranslations();
	}
	
	protected function registry(): ModuleRegistry
	{
		if (null === $this->registry) {
			$this->registry = $this->app->make(ModuleRegistry::class);
		}
		
		return $this->registry;
	}
	
	protected function autoDiscoveryHelper(): AutoDiscoveryHelper
	{
		if (null === $this->auto_discovery_helper) {
			$this->auto_discovery_helper = $this->app->make(AutoDiscoveryHelper::class);
		}
		
		return $this->auto_discovery_helper;
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
			ModulesCache::class,
			ModulesClear::class,
			ModulesSync::class,
			ModulesList::class,
		]);
	}
	
	protected function bootRoutes(): void
	{
		if ($this->app->routesAreCached()) {
			return;
		}
		
		$this->autoDiscoveryHelper()
			->routeFileFinder()
			->each(function(SplFileInfo $file) {
				require $file->getRealPath();
			});
	}
	
	protected function bootViews(): void
	{
		$this->callAfterResolving('view', function(ViewFactory $view_factory) {
			$this->autoDiscoveryHelper()
				->viewDirectoryFinder()
				->each(function(SplFileInfo $directory) use ($view_factory) {
					if (!$module = $this->registry()->moduleForPath($directory->getPath())) {
						throw new RuntimeException("Unable to determine module for '{$directory->getPath()}'");
					}
					
					$view_factory->addNamespace($module->name, $directory->getRealPath());
				});
		});
	}
	
	protected function bootBladeComponents() : void
	{
		$this->callAfterResolving(BladeCompiler::class, function(BladeCompiler $blade) {
			$this->autoDiscoveryHelper()
				->bladeComponentFileFinder()
				->each(function(SplFileInfo $component) use ($blade) {
					if (!$module = $this->registry()->moduleForPath($component->getPath())) {
						throw new RuntimeException("Unable to determine module for '{$component->getPath()}'");
					}
					
					$fully_qualified_component = $this->pathToFullyQualifiedClassName($component->getPathname(), $module);
					$blade->component($fully_qualified_component, null, $module->name);
				});
		});
	}
	
	protected function bootLivewireComponents(): void
    	{
		if (class_exists('Livewire\\Livewire')) {
		    $this->autoDiscoveryHelper()
			->livewireComponentFileFinder()
			->each(function (SplFileInfo $component) {
			    if (!$module = $this->registry()->moduleForPath($component->getPath())) {
				throw new RuntimeException("Unable to determine module for '{$component->getPath()}'");
			    }
			    $componentName = Str::of($component->getBasename('.php'))->kebab();
			    \Livewire\Livewire::component($module->name . '::' . $componentName, $this->pathToFullyQualifiedClassName($component->getPathname(), $module));
			});
		}
    	}
	
	protected function bootTranslations() : void
	{
		$this->callAfterResolving('translator', function(TranslatorContract $translator) {
			if (!$translator instanceof Translator) {
				return;
			}
			
			$this->autoDiscoveryHelper()
				->langDirectoryFinder()
				->each(function(SplFileInfo $directory) use ($translator) {
					if (!$module = $this->registry()->moduleForPath($directory->getPath())) {
						throw new RuntimeException("Unable to determine module for '{$directory->getPath()}'");
					}
					
					$path = $directory->getRealPath();
					
					$translator->addNamespace($module->name, $path);
					$translator->addJsonPath($path);
				});
		});
	}
	
	/**
	 * This functionality is likely to go away at some point so don't rely
	 * on it too much. The package has been abandoned.
	 */
	protected function bootBreadcrumbs() : void
	{
		$class_name = 'Diglactic\\Breadcrumbs\\Manager';
		
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
	
	protected function registerMigrations(Migrator $migrator) : void
	{
		$this->autoDiscoveryHelper()
			->migrationDirectoryFinder()
			->each(function(SplFileInfo $path) use ($migrator) {
				$migrator->path($path->getRealPath());
			});
	}
	
	protected function registerEloquentFactories() : void
	{
		EloquentFactory::guessFactoryNamesUsing(function($model_name) {
			// Because Factory::$namespace is protected, we need to access it via reflection.
			// Hopefully we can PR something into Laravel to make this less hacky.
			$reflection = new ReflectionProperty(EloquentFactory::class, 'namespace');
			$reflection->setAccessible(true);
			$factory_namespace = $reflection->getValue();
			
			$modules_namespace = config('app-modules.modules_namespace', 'Modules');
			
			if (
				Str::startsWith($model_name, $modules_namespace)
				&& $module = $this->registry()->moduleForClass($model_name)
			) {
				$model_name = Str::startsWith($model_name, $module->qualify('Models\\'))
					? Str::after($model_name, $module->qualify('Models\\'))
					: Str::after($model_name, $module->namespace());
				
				return $module->qualify($factory_namespace.$model_name.'Factory');
			}
			
			$model_name = Str::startsWith($model_name, 'App\\Models\\')
				? Str::after($model_name, 'App\\Models\\')
				: Str::after($model_name, 'App\\');
			
			return $factory_namespace.$model_name.'Factory';
		});
	}
	
	protected function registerLegacyFactories(LegacyEloquentFactory $factory) : void
	{
		$this->autoDiscoveryHelper()
			->factoryDirectoryFinder()
			->each(function(SplFileInfo $path) use ($factory) {
				$factory->load($path->getRealPath());
			});
	}
	
	protected function registerPolicies(Gate $gate) : void
	{
		$this->autoDiscoveryHelper()
			->modelFileFinder()
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
				}
				
				// If that doesn't match, try the simple mapping as well
				// i.e. Models/Foo/Bar -> Policies/BarPolicy
				if (false !== strpos($namespaced_model, '\\')) {
					$simple_model = Str::afterLast($fully_qualified_model, '\\');
					$simple_policy = rtrim($module->namespaces->first(), '\\').'\\Policies\\'.$simple_model.'Policy';
					
					if (class_exists($simple_policy)) {
						$gate->policy($fully_qualified_model, $simple_policy);
					}
				}
			});
	}
	
	protected function registerCommands(Artisan $artisan): void
	{
		$this->autoDiscoveryHelper()
			->commandFileFinder()
			->each(function(SplFileInfo $file) use ($artisan) {
				if (!$module = $this->registry()->moduleForPath($file->getPath())) {
					throw new RuntimeException("Unable to determine module for '{$file->getPath()}'");
				}
				
				$class_name = $this->pathToFullyQualifiedClassName($file->getPathname(), $module);
				if ($this->isInstantiableCommand($class_name)) {
					$artisan->resolve($class_name);
				}
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
	
	protected function pathToFullyQualifiedClassName($path, ModuleConfig $module_config) : string
	{
		foreach ($module_config->namespaces as $namespace_path => $namespace) {
			if (0 === strpos($path, $namespace_path)) {
				$relative_path = Str::after($path, $namespace_path);
				return $namespace.$this->formatPathAsNamespace($relative_path);
			}
		}
		
		throw new RuntimeException("Unable to infer qualified class name for '{$path}'");
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
	
	protected function isInstantiableCommand($command) : bool
	{
		return is_subclass_of($command, Command::class)
			&& !(new ReflectionClass($command))->isAbstract();
	}
}
