# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [1.0.0-beta8] - 2025-02-04

### Added

- `DriverInfo` class
- `Connection::getDriverInfo(): DriverInfo` method to retrieve infos from connection
- `Connection::fromPdo(): Connection` method to create connection from PDO object

### Changed

- Exclude logger from serialization of `Connection`

### Deprecated

- `Connection::getDriverName(): string`

## [1.0.0-beta7] - 2024-09-25

### Changed

- `Connection::__construct()` accepts username or password parameters

## [1.0.0-beta6] - 2022-09-05

### Added

- New method `Connection::yieldAll()`
- New method `Connection::yieldColumn()`

### Changed

- `Connection::fetchAll()` no longer uses `Generator`
- `Connection::fetchColumn()` no longer uses `Generator`

## [1.0.0-beta5] - 2022-06-24

### Added

- Bind parameters list

## [1.0.0-beta4] - 2022-02-19

### Added

- Support of PHP 8.1 enums

## [1.0.0-beta3] - 2021-08-27

### Added

- New method `Connection::getDriverName(): ?string`

## [1.0.0-beta2] - 2021-07-07

### Changed

- Add type of log (connection or query)

### Removed

- @package attributes from PhpDoc

## [1.0.0-beta1] - 2021-06-02

Initial development.
