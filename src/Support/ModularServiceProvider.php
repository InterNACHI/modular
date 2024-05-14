<?php

namespace InterNACHI\Modular\Support;

use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Migrations\Migrator;
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
use Livewire\Livewire;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;

class ModularServiceProvider extends ServiceProvider
{
	protected ?ModuleRegistry $registry = null;
	
	protected ?AutoDiscoveryHelper $auto_discovery_helper = null;
	
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
				$this->app->bootstrapPath('cache/modules.php')
			);
		});
		
		$this->app->singleton(AutoDiscoveryHelper::class);
		
		$this->app->singleton(MakeMigration::class, function($app) {
			return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
		});
		
		$this->registerEloquentFactories();
		
		// Set up lazy registrations for things that only need to run if we're using
		// that functionality (e.g. we only need to look for and register migrations
		// if we're running the migrator)
		$this->registerLazily(Migrator::class, [$this, 'registerMigrations']);
		$this->registerLazily(Gate::class, [$this, 'registerPolicies']);
		
		// Look for and register all our commands in the CLI context
		Artisan::starting(Closure::fromCallable([$this, 'registerCommands']));
	}
	
	public function boot(): void
	{
        $this->publishVendorFiles();

        if ($this->shouldDiscover('commands')) {
            $this->bootPackageCommands();
        }

        if ($this->shouldDiscover('routes')) {
            $this->bootRoutes();
        }

        if ($this->shouldDiscover('breadcrumbs')) {
            $this->bootBreadcrumbs();
        }

        if ($this->shouldDiscover('views')) {
            $this->bootViews();
        }

        if ($this->shouldDiscover('blade_components')) {
            $this->bootBladeComponents();
        }

        if ($this->shouldDiscover('translations')) {
            $this->bootTranslations();
        }

        if ($this->shouldDiscover('livewire_components')) {
            $this->bootLivewireComponents();
        }
	}
	
	protected function registry(): ModuleRegistry
	{
		return $this->registry ??= $this->app->make(ModuleRegistry::class);
	}
	
	protected function autoDiscoveryHelper(): AutoDiscoveryHelper
	{
		return $this->auto_discovery_helper ??= $this->app->make(AutoDiscoveryHelper::class);
    }

    protected function shouldDiscover(string $component): bool
    {
        return ! in_array($component,  config('app-modules.dont_discover', []));
    }
	
	protected function publishVendorFiles(): void
	{
		$this->publishes([
			"{$this->base_dir}/config.php" => $this->app->configPath('app-modules.php'),
		], 'modular-config');
	}
	
	protected function bootPackageCommands(): void
    {
		if (! $this->app->runningInConsole()) {
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
					$module = $this->registry()->moduleForPathOrFail($directory->getPath());
					$view_factory->addNamespace($module->name, $directory->getRealPath());
				});
		});
	}
	
	protected function bootBladeComponents(): void
	{
		$this->callAfterResolving(BladeCompiler::class, function(BladeCompiler $blade) {
			// Boot individual Blade components (old syntax: `<x-module-* />`)
			$this->autoDiscoveryHelper()
				->bladeComponentFileFinder()
				->each(function(SplFileInfo $component) use ($blade) {
					$module = $this->registry()->moduleForPathOrFail($component->getPath());
					$fully_qualified_component = $module->pathToFullyQualifiedClassName($component->getPathname());
					$blade->component($fully_qualified_component, null, $module->name);
				});
			
			// Boot Blade component namespaces (new syntax: `<x-module::* />`)
			$this->autoDiscoveryHelper()
				->bladeComponentDirectoryFinder()
				->each(function(SplFileInfo $component) use ($blade) {
					$module = $this->registry()->moduleForPathOrFail($component->getPath());
					$blade->componentNamespace($module->qualify('View\\Components'), $module->name);
				});
		});
	}
	
	protected function bootTranslations(): void
	{
		$this->callAfterResolving('translator', function(TranslatorContract $translator) {
			if (! $translator instanceof Translator) {
				return;
			}
			
			$this->autoDiscoveryHelper()
				->langDirectoryFinder()
				->each(function(SplFileInfo $directory) use ($translator) {
					$module = $this->registry()->moduleForPathOrFail($directory->getPath());
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
	protected function bootBreadcrumbs(): void
	{
		$class_name = 'Diglactic\\Breadcrumbs\\Manager';
		
		if (! class_exists($class_name)) {
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
	
	protected function bootLivewireComponents(): void
	{
		if (! class_exists(Livewire::class)) {
			return;
		}
		
		$this->autoDiscoveryHelper()
			->livewireComponentFileFinder()
			->each(function(SplFileInfo $component) {
				$module = $this->registry()->moduleForPathOrFail($component->getPath());
				
				$component_name = Str::of($component->getRelativePath())
					->explode('/')
					->filter()
					->push($component->getBasename('.php'))
					->map([Str::class, 'kebab'])
					->implode('.');
				
				$fully_qualified_component = $module->pathToFullyQualifiedClassName($component->getPathname());
				
				Livewire::component("{$module->name}::{$component_name}", $fully_qualified_component);
			});
	}
	
	protected function registerMigrations(Migrator $migrator): void
	{
		$this->autoDiscoveryHelper()
			->migrationDirectoryFinder()
			->each(function(SplFileInfo $path) use ($migrator) {
				$migrator->path($path->getRealPath());
			});
	}
	
	protected function registerEloquentFactories(): void
	{
		$helper = new DatabaseFactoryHelper($this->registry());
		
		EloquentFactory::guessModelNamesUsing($helper->modelNameResolver());
		EloquentFactory::guessFactoryNamesUsing($helper->factoryNameResolver());
	}
	
	protected function registerPolicies(Gate $gate): void
	{
		$this->autoDiscoveryHelper()
			->modelFileFinder()
			->each(function(SplFileInfo $file) use ($gate) {
				$module = $this->registry()->moduleForPathOrFail($file->getPath());
				$fully_qualified_model = $module->pathToFullyQualifiedClassName($file->getPathname());
				
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
				$module = $this->registry()->moduleForPathOrFail($file->getPath());
				$class_name = $module->pathToFullyQualifiedClassName($file->getPathname());
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
	
	protected function getModulesBasePath(): string
	{
		if (null === $this->modules_path) {
			$directory_name = $this->app->make('config')->get('app-modules.modules_directory', 'app-modules');
			$this->modules_path = str_replace('\\', '/', $this->app->basePath($directory_name));
		}
		
		return $this->modules_path;
	}
	
	protected function isInstantiableCommand($command): bool
	{
		return is_subclass_of($command, Command::class)
			&& ! (new ReflectionClass($command))->isAbstract();
	}
}
