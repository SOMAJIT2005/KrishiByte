# Local environment — verified 12 October 2026

- Workspace: `D:\KrishiByte`
- PHP: 8.2.33; Laravel: 12.69.3; PHPUnit: 11.5.57
- Wampserver MySQL: 8.0.46, running locally
- Application database: `krishibyte`
- Standard test database: `krishibyte_test`
- Fresh-install QA database: `krishibyte_upgrade_qa`
- Local website: http://127.0.0.1:8000

The website was started using `php -S 127.0.0.1:8000 -t public`, backed by WAMP MySQL. This is a local development server, not a public deployment. Restart it with the same command if the session ends.

## Setup and safe upgrades

Use `.env.example` to configure your own `.env`, application key and database credentials. Run `php artisan migrate`, then `php artisan db:seed` for illustrative local data. Do not use `migrate:fresh` on the application database. The two upgrade migrations were applied without resetting existing data. Seeders preserve existing account credentials and existing demo records.

For tests, create a separate test database and configure `.env.testing` / `phpunit.xml`. Migrate and seed that database before running the transaction-based test suite.

```powershell
$env:DB_DATABASE = 'krishibyte_test'
php artisan migrate --env=testing
php artisan db:seed --env=testing
php -d memory_limit=256M vendor/bin/phpunit
Remove-Item Env:DB_DATABASE
```

Only use a database you intend for testing. Test cases roll back their changes. The fresh QA database was migrated and seeded twice to check clean setup and repeat seeding.

## Evidence and credentials

New report attachments use the private Laravel local disk. To migrate legacy public attachments, run `php artisan reports:secure-evidence`; the command verifies the private copy before removing the old public file. No legacy images were found in this local database.

`.env` was previously tracked. It is now removed from the Git index and ignored while the local file remains intact. Old Git history still contains the earlier file: rotate any real credentials and clean history before sharing the repository publicly. No history rewrite was performed.

The local-only demo login shortcuts use illustrative accounts. SMS remains a mock adapter. Actual production hosting, credentials, authentication hardening for deployment, and an SMS provider require deployment-specific configuration.

Original source/document backups are at `D:\KrishiByte-backup-20261012`. No application database reset or public deployment was performed.
