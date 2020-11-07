# Changelog

This changelog follows the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format,
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Adds support for `php artisan make:component`
- `php artisan modules:sync` will now update additional PhpStorm config files
- Partial support for `--all` on `make:model`
- Initial support for component auto-discovery
- Switched to single `app-modules/*` composer repository rather than new repositories for each module
- Added description field to generated `composer.json` file
- Moved tests from `autoload-dev` to `autoload` because composer doesn't support 
  `autoload-dev` for non-root configs  

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

[unreleased]: https://github.com/InterNACHI/modular/compare/1.0.1...HEAD
[1.0.1]: https://github.com/InterNACHI/modular/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/InterNACHI/modular/releases/tag/1.0.0
