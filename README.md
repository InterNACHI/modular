# InterNACHI/Modular

`InterNACHI/Modular` is a module system for Laravel applications. It aims to re-create
as little as possible, using composer for module resolution and all the existing
Laravel conventions and commands across the board.

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
app-modules
  my-module
    src
      Providers
        MyModuleServiceProvider.php
      Console
        Commands
    tests
      MyModuleServiceProviderTest.php
    routes
      my-module-routes.php
    resources
      views
        index.blade.php
        create.blade.php
        show.blade.php
        edit.blade.php
    database
      migrations
        2020_07_05_194042_set_up_my-module_module.php
      factories
      seeds
```

It will also add two new entries to your `composer.json` file:

```json
{
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
    "modules/my-module": "*"
  }
}
```

The `repositories` entry tells composer to symlink `app-modules/my-module` to
the `vendor/` directory and treat it like any other composer package. The
`require` statement then tells composer to install that package.

Modular will helpfully remind you to perform a composer update, so let's do
that now:

```shell script
composer update modules/my-module
```

And now we're all set.
