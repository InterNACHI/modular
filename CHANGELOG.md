# Changelog

This changelog follows the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format,
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.6.0]

### Added

- Added support for custom module stubs
- Added support for syncing modules to PhpStorm library roots

### Fixed

- Only  register the `make:livewire` integration if Livewire is installed

## [1.5.1]

### Added

- Added support for `make:cast`

## [1.5.0]

### Added

- Added support for Livewire's `make:livewire` command

## [1.4.0]

### Added

- Added support for `--module` in `php artisan db:seed`

### Fixed

- Create seeders in the correct namespace when `--module` flag is used in Laravel 8+
- Create factories in the correct namespace when `--module` flag is used in Laravel 8+
- Apply module namespace to models when creating a factory in a module

## [1.3.1]

### Fixed

- Added better handling of missing directories

## [1.3.0]

### Added

- Added support for translations in modules

### Changed

- Switched to `diglactic/laravel-breadcrumbs` for breadcrumbs check

## [1.2.2]

### Added
- Added better patching for PHPStorm config files to minimize diffs

## [1.2.0]

### Added
- Support for auto-registering Laravel 8 factory classes

### Fixed
- Better Windows support
- Support for composer 2.0
- Improves the file scanning efficiency of the `AutoDiscoveryHelper`

## [1.1.0]

### Added
- Adds support for `php artisan make:component`
- `php artisan modules:sync` will now update additional PhpStorm config files
- Partial support for `--all` on `make:model`
- Initial support for component auto-discovery
- Switched to single `app-modules/*` composer repository rather than new repositories for each module
- Added description field to generated `composer.json` file
- Moved tests from `autoload-dev` to `autoload` because composer doesn't support 
  `autoload-dev` for non-root configs
- Added improved support for Laravel 8 factory classes  

## [1.0.1]

### Changed
- Introduces a few improvements to the default composer.json format.

## [1.0.0]

### Added
- Initial release

--------

#### "Keep a Changelog" - Types of Changes

- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities.

[Unreleased]: https://github.com/InterNACHI/modular/compare/1.6.0...HEAD
[1.6.0]: https://github.com/InterNACHI/modular/compare/1.5.1...1.6.0
[1.5.1]: https://github.com/InterNACHI/modular/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/InterNACHI/modular/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/InterNACHI/modular/compare/1.3.1...1.4.0
[1.3.1]: https://github.com/InterNACHI/modular/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/InterNACHI/modular/compare/1.2.2...1.3.0
[1.2.2]: https://github.com/InterNACHI/modular/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/InterNACHI/modular/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/InterNACHI/modular/compare/1.1.0...1.2.0
[1.0.1]: https://github.com/InterNACHI/modular/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/InterNACHI/modular/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/InterNACHI/modular/releases/tag/1.0.0
