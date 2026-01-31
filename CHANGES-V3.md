# Architecture Changes Summary: Plugin-Based Autodiscovery System

## Overview

This release introduces a **plugin-based architecture** for module auto-discovery, replacing the monolithic `ModularServiceProvider` 
with a collection of focused, composable `Plugin` classes. This is a significant architectural refactoring that improves extensibility, 
testability, and separation of concerns.

## Breaking Changes

1. **PHP 8.3+ required** (was 8.0+)
2. **Laravel 11+ required** (dropped Laravel 9/10 support)
3. **`ModularEventServiceProvider` removed** from auto-registered providers
4. **Cache file location changed**: `bootstrap/cache/modules.php` → `bootstrap/cache/app-modules.php`
5. **Cache format changed**: Now stores all plugin discovery data, keyed by plugin FQCN
6. **`ModuleRegistry::getCachePath()` removed**
7. **`AutoDiscoveryHelper` renamed** to `FinderFactory` (breaking for any code that type-hinted it)

## New Plugin Architecture

### Core Abstractions

**`Plugin` (abstract base class)**

```php
abstract class Plugin {
    abstract public function discover(FinderFactory $finders): iterable;
    abstract public function handle(Collection $data);
}
```

Each plugin implements a two-phase lifecycle:

1. **`discover()`** - Scan filesystem and return serializable discovery data
2. **`handle()`** - Process the discovered data (register with Laravel services)

**`AutodiscoveryHelper`** - Orchestrates plugin lifecycle:

- Manages plugin registration and instantiation
- Handles caching (read/write) for all plugins in a single file
- Uses PHP 8 attributes to determine plugin boot timing
- Prevents duplicate `handle()` execution via `$handled` tracking

**`PluginRegistry`** - Static registration point for plugins (enables third-party plugins)

### PHP 8 Attributes for Lifecycle Control

```php
#[AfterResolving(BladeCompiler::class, parameter: 'blade')]
class BladePlugin extends Plugin { ... }
```

- **`#[AfterResolving]`** - Defers plugin execution until a specific service is resolved from the container. The `parameter` argument enables dependency injection of the resolved service.
- **`#[OnBoot]`** - Executes plugin immediately during `booting()` hook

This attribute-based approach replaces the previous `$this->callAfterResolving()` and `$this->app->resolving()` patterns with a declarative system.

### Built-in Plugins

| Plugin             | Trigger                                       | Responsibility                                                     |
|--------------------|-----------------------------------------------|--------------------------------------------------------------------|
| `ModulesPlugin`    | Eager                                         | Discovers `composer.json` files, creates `ModuleConfig` instances  |
| `RoutesPlugin`     | Conditional (`!routesAreCached()`)            | Loads route files from modules                                     |
| `ViewPlugin`       | `AfterResolving(ViewFactory)`                 | Registers view namespaces                                          |
| `BladePlugin`      | `AfterResolving(BladeCompiler)`               | Registers Blade components with module prefixes                    |
| `TranslatorPlugin` | `AfterResolving(Translator)`                  | Registers translation namespaces + JSON paths                      |
| `EventsPlugin`     | `AfterResolving(Dispatcher)`                  | Discovers event listeners (honors `should_discover_events` config) |
| `MigratorPlugin`   | `AfterResolving(Migrator)`                    | Registers migration paths                                          |
| `GatePlugin`       | `AfterResolving(Gate)`                        | Auto-registers model policies                                      |
| `ArtisanPlugin`    | `Artisan::starting()`                         | Registers console commands + Tinker namespaces                     |

## Key Design Decisions

1. **Unified caching** - All plugin discovery data is stored in a single cache file, keyed by plugin FQCN. This simplifies cache invalidation and reduces filesystem operations.
2. **Lazy instantiation** - Plugins are only instantiated when their trigger fires. The `$handled` array ensures each plugin's `handle()` runs at most once per request.
3. **Separation of concerns** - `FinderFactory` now only creates `FinderCollection` instances. The business logic of what to do with discovered files lives in plugins.
4. **`ModuleFileInfo` decorator** - New wrapper around `SplFileInfo` that provides `module()` and `fullyQualifiedClassName()` helpers, reducing boilerplate in plugins.
5. **`ModuleRegistry` simplified** - No longer responsible for caching; delegates to `AutodiscoveryHelper`. The `modules()` method now returns the result of `ModulesPlugin::handle()`.

## Flow Diagram

```
ModularServiceProvider::register()
    │
    ├─→ Register singletons (ModuleRegistry, FinderFactory, AutodiscoveryHelper)
    │
    └─→ PluginRegistry::register(plugins...)
           │
           └─→ $app->booting(bootPlugins)
                   │
                   ├─→ AutodiscoveryHelper::register() for each plugin
                   │
                   ├─→ AutodiscoveryHelper::bootPlugins()
                   │       │
                   │       └─→ For each plugin, read attributes:
                   │               • AfterResolving → $app->afterResolving(callback)
                   │               • OnBoot → handle() immediately
                   │
                   └─→ Conditional plugin handling (Routes, Livewire)
                       Artisan::starting() callback for ArtisanPlugin
```

## Extensibility

Third-party packages or application code can register custom plugins:

```php
PluginRegistry::register(MyCustomPlugin::class);
```

The plugin will be automatically integrated into the caching system and can use the attribute-based lifecycle controls.

## Testing Impact

- Tests now use `ModulesCache::class` and `ModulesClear::class` instead of `event:cache`/`event:clear`
- Event discovery tests assert against the unified `app-modules.php` cache format
- `AutoDiscoveryHelperTest` renamed to test `FinderFactory` directly
