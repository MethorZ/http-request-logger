# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1] - 2026-10-22

### Fixed
- `LoggingMiddleware` no longer fails with "Interface Monolog\Processor\ProcessorInterface not found" when the optional `monolog/monolog` package is not installed. `RequestIdProcessor` no longer implements Monolog's `ProcessorInterface`; Monolog still accepts it as a callable processor.

## [1.2.0] - 2026-10-22

### Changed
- PHP requirement raised to `~8.4.27 || ~8.5.12` (releases patched for php2shell: CVE-2026-109618, CVE-2026-109619). PHP 8.2 and 8.3 are no longer supported.
- Updated development dependencies: phpunit/phpunit ^13.4, phpstan/phpstan ^2.3, slevomat/coding-standard ^8.31, squizlabs/php_codesniffer ^4.0.1, monolog/monolog ^3.12.

## [1.0.0] - 2025-11-27

### Added
- Initial release of HTTP Request Logger middleware
- PSR-3 HTTP request logging middleware
- Request ID generation and tracking
- Performance monitoring (response time)
- Automatic request/response logging
- PSR-15 middleware implementation
- Customizable log levels
- Request ID injection into responses
- Structured logging support
- Framework-agnostic implementation
- Zero-configuration default setup
- Comprehensive test suite
- Complete documentation with examples

[1.2.1]: https://github.com/methorz/http-request-logger/releases/tag/v1.2.1
[1.2.0]: https://github.com/methorz/http-request-logger/releases/tag/v1.2.0
[1.0.0]: https://github.com/methorz/http-request-logger/releases/tag/v1.0.0
