# Deployment and runtime structure

## Runtime versus development

Keep business code in `app/`, schema/history in `database/`, HTTP routes in `routes/`, Blade UI in `resources/views/`, and browser assets in `public/`. `storage/` holds private evidence, sessions and logs. `bootstrap/cache/` holds framework caches. Expose only `public/` through the web server.

There is no Node build. Removed unused welcome/Vite/Tailwind/JavaScript scaffolding and copied PowerShell executable/config. Windows launchers use installed PowerShell. Maintained launch, demo and backup utilities remain in `tools/`; automated regression tests and the concurrency harness (`tests/support/concurrency-check.php`) remain together in `tests/`.

For a production release, exclude `.git/`, `.env.testing`, `phpunit.xml`, `.phpunit.result.cache`, `tests/`, local logs/PID files, demo launchers and local `tools/`. Keep tests in development/CI. Install PHP runtime dependencies from the lockfile with `--no-dev`; do not delete the running application's `vendor/`. Documentation screenshots are not private uploaded agreements.

Also exclude `database/sql/` and `storage/manual-fixture-build/` from production: their data/credentials and public encryption key are deliberately synthetic testing fixtures. Both schema and data SQL files remain together in the development repository. The website has no showcase route or password-free role-login route; all roles use normal login.

The default DatabaseSeeder and test-account UserSeeder reject non-local/test environments, including forced CLI seeding. Provision real production accounts with unique credentials through a trusted deployment process; do not copy the synthetic user database. This guard does not remove test accounts already present in an existing database.

Never copy a development environment, dummy SMS logs, sessions, test DB or seeded demo credentials into a new production environment. Existing private evidence and encryption keys require protected migration/backups; do not delete them as cleanup.

## Deployment sequence

Use a separate release directory and production environment. This local WAMP installation is not being converted to production in place.

1. Back up the database, private evidence and matching APP_KEY; verify restoration before upgrading.
2. Install PHP 8.2+ with this project's required extensions, including bcmath, gd, pdo_mysql and mbstring. Set up a least-privilege DB account.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`. Provision `.env` securely with APP_ENV=production, APP_DEBUG=false, the HTTPS APP_URL, proper DB credentials and secure cookies. Preserve the existing key for encrypted data; generate one only for a brand-new empty installation.
4. Configure Apache/Nginx/PHP-FPM to serve `public/index.php`. Grant the service user write access to `storage/` and `bootstrap/cache/`, without world-writable permissions. Evidence remains private.
5. Apply reviewed migrations with `php artisan migrate --force`, then `php artisan optimize`. Do not seed local demo accounts or run showcase:populate on production.
6. Smoke-test login, assigned reviews, middleman movement histories and retail receipt/quote pages. Monitor errors and evidence storage. Configure workers if queue-backed work is enabled.
7. Retain the old release for rollback. History-preserving migrations can refuse destructive reversal; restore a verified matching database/evidence/key snapshot rather than forcing down migrations.

The public web root, writable framework directories, cache optimization and disabled debug mode follow [Laravel 12 deployment guidance](https://laravel.com/docs/12.x/deployment). The project still requires the MFA/contact verification, backup/retention and independent security work listed in README and SECURITY_REVIEW.md.

## Cleanup on 13 October 2026

Removed unused scaffold files, two ExampleTest placeholders and seven historical `.txt` test-output files. Removed-file backups are outside the app at `D:\KrishiByte-maintenance-20261013`. Preserved regression tests, Composer lockfile, actual app logs, running server files, local environment and all uploads/database records.

The final follow-up grouped the concurrency harness under `tests/support`, removed six stale generic/8011 test-server files after checking their PIDs/listener, and replaced unrelated ignore rules. The active port-8000 server remains running. Its launcher, the relocated concurrency test and nine README screenshot paths were verified.
