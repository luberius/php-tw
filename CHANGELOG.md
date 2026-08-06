# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

## [1.1.0] - 2026-08-06

### Security

- Require security-fixed Symfony Process, Symfony Cache, PHPUnit, and TailwindCSS releases.
- Use argument-array process execution for development and build commands.
- Harden project scaffolding against unchecked filesystem failures and symlink traversal.

### Fixed

- Register `BuildCommand` under the namespace expected by command discovery.
- Return failure status when build steps or development processes fail.
- Support platforms without `pcntl` or the sockets extension.
- Track the Composer lock file and PHPUnit configuration for reproducible clean checkouts.

### Changed

- Raise the minimum supported PHP version to 8.1.
- Document Tailwind CSS v4 CSS-first configuration and production builds.
- Expand isolated tests for scaffolding, build failures, and command discovery without downloading the Tailwind binary.

## [1.0.5] - 2024-09-22

- Fix Tailwind CSS configuration.

[Unreleased]: https://github.com/luberius/php-tw/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/luberius/php-tw/compare/v1.0.5...v1.1.0
[1.0.5]: https://github.com/luberius/php-tw/releases/tag/v1.0.5
