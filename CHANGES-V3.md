# Version 3.0 Architecture Changes

## Overview

Version 3.0 introduces a **plugin-based architecture** for module auto-discovery. The monolithic `ModularServiceProvider` has been refactored into a collection of focused, composable `Plugin` classes.
This improves extensibility, testability, and separation of concerns.

## Breaking Changes

### Requirements

- **PHP 8.3+** (was 8.0+)
- **Laravel 11+** (dropped Laravel 9/10 support)

### Removed Features

| Removed                          | Replacement                           |
|----------------------------------|---------------------------------------|
| `ModularEventServiceProvider`    | `EventsPlugin` (auto-registered)      |
| `AutoDiscoveryHelper`            | `FinderFactory`                       |
| `ModuleRegistry::getCachePath()` | `Cache::path()`                       |
| `make:livewire --module`         | Install `internachi/modular-livewire` |
| Breadcrumbs integration          | Load manually in service provider     |

### Cache Changes

| Before                        | After                             |
|-------------------------------|-----------------------------------|
| `bootstrap/cache/modules.php` | `bootstrap/cache/app-modules.php` |
| Module paths only             | All plugin discovery data         |

## Plugin Architecture

### Core Classes

```
PluginRegistry       → Registration point for plugins
PluginHandler        → Orchestrates plugin lifecycle
PluginDataRepository → Manages discovery data (cached or fresh)
Cache                → File I/O for unified cache
FinderFactory        → Creates FinderCollection instances
ModuleFileInfo       → Decorator with module() and fullyQualifiedClassName()
```

### Plugin Base Class

```php
abstract class Plugin
{
    abstract public function discover(FinderFactory $finders): iterable;
    abstract public function handle(Collection $data);
}
```

### PHP 8 Attributes

| Attribute                           | Behavior                                    |
|-------------------------------------|---------------------------------------------|
| `#[OnRegister]`                     | Execute during `register()` phase           |
| `#[AfterResolving(Service::class)]` | Defer until service resolved                |
| `#[OnBoot]`                         | Execute during `booting()` hook             |
| *(none)*                            | Explicit call via `PluginHandler::handle()` |

Plugins that need to run early (before services are resolved) should use `#[OnRegister]`.
This is useful for configuration loading, where values must be available before other
services boot.

### Built-in Plugins

| Plugin             | Trigger                         | Responsibility                                  |
|--------------------|---------------------------------|-------------------------------------------------|
| `ModulesPlugin`    | Eager                           | Discover `composer.json`, create `ModuleConfig` |
| `RoutesPlugin`     | `!routesAreCached()`            | Load route files                                |
| `ViewPlugin`       | `AfterResolving(ViewFactory)`   | Register view namespaces                        |
| `BladePlugin`      | `AfterResolving(BladeCompiler)` | Register Blade components                       |
| `TranslatorPlugin` | `AfterResolving(Translator)`    | Register translations                           |
| `EventsPlugin`     | `AfterResolving(Dispatcher)`    | Discover event listeners                        |
| `MigratorPlugin`   | `AfterResolving(Migrator)`      | Register migration paths                        |
| `GatePlugin`       | `AfterResolving(Gate)`          | Register model policies                         |
| `ArtisanPlugin`    | `Artisan::starting()`           | Register commands                               |

## Lifecycle Flow

```
ModularServiceProvider::register()
    ├─ Register singletons
    ├─ PluginRegistry::add(built-in plugins)
    ├─ PluginHandler::register(app)
    │       └─ For each plugin with #[OnRegister]:
    │               └─ Plugin::handle(data)
    └─ $app->booting(PluginHandler::boot)
            └─ For each plugin with boot attributes:
                    └─ Plugin::boot(handler, app)
                            └─ Read attributes, schedule execution
```

## Extensibility

Register custom plugins in a service provider:

```php
public function register(): void
{
    PluginRegistry::register(MyPlugin::class);
}
```

Custom plugins:

- Must extend `InterNACHI\Modular\Plugins\Plugin`
- Are automatically integrated into caching
- Can use lifecycle attributes

## Laravel Integration

Modular 3.0 integrates with Laravel's optimize commands via the `optimizes()` method:

- `php artisan optimize` → runs `modules:cache`
- `php artisan optimize:clear` → runs `modules:clear`

## Migration Checklist

1. Upgrade PHP to 8.3+ and Laravel to 11+
2. Run `php artisan modules:clear`
3. Run `composer update internachi/modular`
4. If using Livewire: `composer require internachi/modular-livewire`
5. Update any `AutoDiscoveryHelper` references to `FinderFactory`
6. Remove any `ModularEventServiceProvider` references
