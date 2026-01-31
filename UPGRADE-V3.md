# Upgrade Guide

## Upgrading to 3.0 from 2.x

> **Estimated Upgrade Time:** 5-15 minutes

### Updating Dependencies

Update your `composer.json` to require Modular 3.0:

```json
"internachi/modular": "^3.0"
```

Modular 3.0 requires **PHP 8.3+** and **Laravel 11+**. Please ensure your application meets these requirements before upgrading.

### High Impact Changes

#### ModularEventServiceProvider Removed

**Likelihood Of Impact: High**

The `ModularEventServiceProvider` has been removed. Event discovery is now handled by the `EventsPlugin` which is automatically registered.

If you were explicitly referencing `ModularEventServiceProvider` in your application, remove those references. Event discovery will continue to work automatically.

#### Cache File Location Changed

**Likelihood Of Impact: Medium**

The cache file location has changed from `bootstrap/cache/modules.php` to `bootstrap/cache/app-modules.php`.

After upgrading, clear your cache:

```shell
php artisan modules:clear
```

If you have any deployment scripts that reference the old cache path, update them accordingly.

### Medium Impact Changes

#### AutoDiscoveryHelper Renamed

**Likelihood Of Impact: Low**

`AutoDiscoveryHelper` has been renamed to `FinderFactory`. If your application type-hints `AutoDiscoveryHelper`, update the import:

```php
// Before
use InterNACHI\Modular\Support\AutoDiscoveryHelper;

// After
use InterNACHI\Modular\Support\FinderFactory;
```

#### ModuleRegistry::getCachePath() Removed

**Likelihood Of Impact: Low**

The `getCachePath()` method has been removed from `ModuleRegistry`. Caching is now handled internally by the plugin system.

If you were using this method, you can access the cache path via the `Cache` class:

```php
use InterNACHI\Modular\Support\Cache;

$path = app(Cache::class)->path();
```

#### Config File Location

**Likelihood Of Impact: Very Low**

The internal config file has moved from `config.php` to `config/app-modules.php`. This change is transparent if you published the config file. If you are extending the package and referencing the internal config path, update your references.

### Low Impact Changes

#### Livewire Integration Removed

**Likelihood Of Impact: Low**

The built-in Livewire integration has been moved to a separate package. If you use the `make:livewire` command with the `--module` option, install the companion package:

```shell
composer require internachi/modular-livewire
```

#### Breadcrumbs Support Removed

**Likelihood Of Impact: Very Low**

Support for the `diglactic/laravel-breadcrumbs` package has been removed. The package has been abandoned and this integration was rarely used. If you need this functionality, you can load your breadcrumb files manually in your module's service provider.

### New Features

#### Custom Plugins

You can now extend Modular with custom plugins. Create a class extending `InterNACHI\Modular\Plugins\Plugin` and register it:

```php
use InterNACHI\Modular\PluginRegistry;

PluginRegistry::register(MyCustomPlugin::class);
```

See the [Release Notes](RELEASE-NOTES-V3.md) for more information on the plugin architecture.

#### Optimize Commands

Modular now integrates with Laravel's `optimize` and `optimize:clear` commands. Running `php artisan optimize` will automatically cache your modules.
