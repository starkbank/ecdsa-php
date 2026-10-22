# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to the following versioning pattern:

Given a version number MAJOR.MINOR.PATCH, increment:

- MAJOR version when **breaking changes** are introduced;
- MINOR version when **backwards compatible changes** are introduced;
- PATCH version when backwards compatible bug **fixes** are implemented.


## [Unreleased]

## [2.3.0]
### Changed
- PHP requirement to `~8.2.35 || ~8.3.36 || ~8.4.27 || >=8.5.12`. Earlier PHP versions can no longer install this library
- Distributions that backport the fix without changing the PHP version number may be blocked by this requirement; use `--ignore-platform-req=php` only after confirming the fix in the distribution's advisory
### Security
- Require PHP versions that fix CVE-2026-109618 and CVE-2026-109619 (PHP2Shell), which affect PHP 5.4 through 8.5.11 and allow unauthenticated remote code execution

## [2.2.1] - 2026-05-05
### Fixed
- top-level gmp_init() running on every autoload, breaking transitive dependents without ext-gmp (#27)

## [2.2.0] - 2026-04-28
### Changed
- Security changes

## [2.1.0] - 2023-09-06
### Added
- toCompressed and fromCompressed to PublicKey resource
- modularSquareRoot to Math resource
- Y to curve method

## [2.0.2] - 2022-10-27
### Fixed
- composer.json to map all classes

## [2.0.1] - 2022-10-26
### Fixed
- repeated autoload requirement

## [2.0.0] - 2022-09-22
### Changed
- internal structure to use native php logic instead of openssl
### Added
- Curve::add() function to dynamically add curves to the library
