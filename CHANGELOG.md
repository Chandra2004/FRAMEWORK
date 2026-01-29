# Changelog

All notable changes to **The Framework** will be documented in this file.

## [5.0.0] - 2026-01-29

### Added

- New Web Command Center for easier management.
- Improved ORM with Laravel-like syntax.
- Enhanced security middleware (CSRF, WAF, Auth).
- Built-in Rate Limiting.
- Support for PHP 8.5+.

### Fixed

- Critical Command Injection in `ServeCommand`.
- SVG XSS vulnerability in `UploadHandler`.
- Windows compatibility for URL validation.
- Missing `.gitignore` files in storage.
- `Model::create()` visibility bug.
