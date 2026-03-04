# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- GitHub Actions CI (PHP 8.1/8.2/8.3) running `composer check` and `composer test`.
- PHPStan static analysis (`composer phpstan`, included in `composer check`).
- Error handling contract documentation in README.
- Unit tests for IČO normalization and rate limiting.

### Changed
- IČO normalization is canonicalized to 8 digits (left-padded with zeros).
- README examples updated to match public DTO API.

### Fixed
- Composer PHP version constraint.
- Test expectations for canonical IČO.


