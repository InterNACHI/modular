# InterNACHI/Modular

`InterNACHI/Modular` is a module system for Laravel applications. It aims to re-create
as little as possible, using Composer for module resolution and all the existing
Laravel conventions and commands across the board.

- [Installation](#installation)
- [Usage](#usage)

## Installation

To get started, run:

```shell script
composer require internachi/modular
``` 

Laravel will auto-discover the package and everything will be automatically set up
for you. The next step is to create your first module:

```shell script
php artisan make:module my-module 
```

Modular will scaffold up a new module for you:

```
app-modules/
  my-module/
    src/
      Providers/
        MyModuleServiceProvider.php
      Console/
        Commands/
    tests/
      MyModuleServiceProviderTest.php
    routes/
      my-module-routes.php
    resources/
      views/
        index.blade.php
        create.blade.php
        show.blade.php
        edit.blade.php
    database/
      migrations/
        2099_01_01_999999_set_up_my-module_module.php
      factories/
      seeds/
```

It will also add two new entries to your `composer.json` file:

```json5
{
  // ...
  "repositories": [
    {
      "type": "path",
      "url": "app-modules/my-module",
      "options": {
          "symlink": true
      }
    }
  ],
  "require": {
    // ...
    "modules/my-module": "*"
    // ...
  }
}
```

The `repositories` entry tells Composer to symlink `app-modules/my-module` to
the `vendor/` directory and treat it like any other Composer package. The
`require` statement then tells Composer to install that package.

Modular will helpfully remind you to perform a Composer update, so let's do
that now:

```shell script
composer update modules/my-module
```

### Optional: Customize namespaces & paths

By default, modules will be in the `Modules\` namespace and installed into the
`app-modules/` directory (keeping it nice and close to your `app/` directory).

If you want to change these defaults, you can publish a config by running:

```shell script
php artisan vendor:publish --tag=modular-config
```

The `app-modules/` convention is highly recommended, as it keeps your modules
near the rest of your application in the filesystem tree. If you plan to extract
modules into their own packages at some point in the future, you probably want to
set a new default namespace. This will make it much simpler if you ever do decide
to publish your package separately.

### Optional: For PhpStorm users

`InterNACHI/Modular` provides a useful command for keeping your PhpStorm template
paths in sync with your modules. Either run this command as needed, or add it as
a hook to your `post-autoload-dump` script in your `composer.json`:

```shell script
php artisan module:update-phpstorm-config
```

This command will register all your installed modules with the Laravel plugin so that
your views are available for autocomplete.

## Usage

`InterNACHI/Modular` is, for the most part, a set of conventions using existing
Laravel and Composer features. Modules are loaded using the Composer autoloader,
and are discovered using [Laravel package discovery](https://laravel.com/docs/7.x/packages#package-discovery).

All modules follow existing Laravel conventions, and auto-discovery should work as
expected in most cases:

- Commands are auto-registered with `php artisan`
- Migrations will be run by the migrator
- Factories are auto-loaded for testing
- Policies are auto-discovered for Models

Most things *just work*.

We also add a `--module=` option to most Laravel `make:` commands so that you can
use all the existing tooling that you know. The commands themselves are exactly the
same, which means you can use your [custom stubs](https://laravel.com/docs/7.x/artisan#stub-customization)
and everything else Laravel provides:
                                                                                feature/int-326-internachi-modular ◼
- `php artisan make:controller MyModuleController --module=my-module`
- `php artisan make:command MyModuleCommand --module=my-module`
- `php artisan make:channel MyModuleChannel --module=my-module`
- `php artisan make:event MyModuleEvent --module=my-module`
- `php artisan make:exception MyModuleException --module=my-module`
- `php artisan make:factory MyModuleFactory --module=my-module`
- `php artisan make:job MyModuleJob --module=my-module`
- `php artisan make:listener MyModuleListener --module=my-module`
- `php artisan make:mail MyModuleMail --module=my-module`
- `php artisan make:middleware MyModuleMiddleware --module=my-module`
- `php artisan make:model MyModule --module=my-module`
- `php artisan make:notification MyModuleNotification --module=my-module`
- `php artisan make:observer MyModuleObserver --module=my-module`
- `php artisan make:policy MyModulePolicy --module=my-module`
- `php artisan make:provider MyModuleProvider --module=my-module`
- `php artisan make:request MyModuleRequest --module=my-module`
- `php artisan make:resource MyModule --module=my-module`
- `php artisan make:rule MyModuleRule --module=my-module`
- `php artisan make:seeder MyModuleSeeder --module=my-module`
- `php artisan make:test MyModuleTest --module=my-module`
