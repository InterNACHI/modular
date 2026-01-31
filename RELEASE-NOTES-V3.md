# Release Notes for 3.0

## Plugin-Based Architecture

Modular 3.0 introduces a **plugin-based architecture** that replaces the monolithic service provider with focused, composable plugin classes. This architectural refactoring improves extensibility, testability, and separation of concerns.

### Built-in Plugins

Each aspect of module auto-discovery is now handled by a dedicated plugin:

| Plugin | Responsibility |
|--------|----------------|
| `ModulesPlugin` | Discovers `composer.json` files and creates `ModuleConfig` instances |
| `RoutesPlugin` | Loads route files from modules |
| `ViewPlugin` | Registers view namespaces |
| `BladePlugin` | Registers Blade components with module namespaces |
| `TranslatorPlugin` | Registers translation namespaces and JSON paths |
| `EventsPlugin` | Discovers and registers event listeners |
| `MigratorPlugin` | Registers migration paths |
| `GatePlugin` | Auto-registers model policies |
| `ArtisanPlugin` | Registers console commands |

### Registering Custom Plugins

Third-party packages or application code can register custom plugins:

```php
use InterNACHI\Modular\PluginRegistry;

PluginRegistry::register(MyCustomPlugin::class);
```

Custom plugins will be automatically integrated into the caching system and can use the attribute-based lifecycle controls.

### Plugin Lifecycle

Each plugin implements a two-phase lifecycle:

1. **`discover()`** - Scans the filesystem and returns serializable discovery data
2. **`handle()`** - Processes the discovered data (registers with Laravel services)

PHP 8 attributes control when plugins execute:

```php
use InterNACHI\Modular\Plugins\Attributes\AfterResolving;

#[AfterResolving(BladeCompiler::class)]
class BladePlugin extends Plugin
{
    // Only runs when BladeCompiler is resolved
}
```

## Unified Caching

The `modules:cache` command now caches all plugin discovery data in a single file (`bootstrap/cache/app-modules.php`). This simplifies cache invalidation and reduces filesystem operations during bootstrapping.

## Laravel 11+ & PHP 8.3+

Modular 3.0 requires Laravel 11 or later and PHP 8.3 or later. This allows us to leverage modern PHP features like attributes and constructor property promotion throughout the codebase.

## Streamlined Dependencies

The Livewire integration has been moved to a [separate package](https://github.com/InterNACHI/modular-livewire) to keep the core package focused and reduce dependencies.

## Breaking Changes

Please see the [Upgrade Guide](UPGRADE-V3.md) for a complete list of breaking changes and migration instructions.
