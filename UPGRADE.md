# Upgrade Guide

## Upgrading to 5.0.0 from 4.x

- Update `composer.json` to require `theframework/core: ^5.0`.
- Replace `app/App/Model.php` with the new version.
- Ensure your `.env` file contains the new required variables (see `docs/environment.md`).
- Run `php artisan migrate` if applicable.
