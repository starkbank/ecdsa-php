# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to the following versioning pattern:

Given a version number MAJOR.MINOR.PATCH, increment:

- MAJOR version when **breaking changes** are introduced;
- MINOR version when **backwards compatible changes** are introduced;
- PATCH version when backwards compatible bug **fixes** are implemented.


## [Unreleased]

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
