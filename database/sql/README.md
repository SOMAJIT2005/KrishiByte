# Schema and synthetic manual-test database

Files kept together:

- `schema.sql`: all 28 table definitions and indexes; no DROP DATABASE/TABLE or USE statement.
- `manual_test_data.sql`: synthetic rows in all 28 tables. Inserts only; not an overlay for an existing database.
- `manifest.json`: table counts, SQL hashes, generated evidence hashes and the public fixture encryption key.
- `private-evidence/evidence/`: eight generated agreement images referenced by the SQL records.

The export was built from a separate, newly migrated synthetic database. It contains no dump of the working installation's private data, environment, real sessions or real reset tokens. Its schema/data were imported into a second isolated database and every row compared successfully.

## Import into a NEW EMPTY database

For the current running installation, use `php artisan manual:populate` instead; do not import these inserts over existing records and do not change its APP_KEY.

For a separate fresh manual-test installation:

1. Create an empty database named `krishibyte_manual` in WAMP/phpMyAdmin.
2. Select that database and import `schema.sql`, then `manual_test_data.sql`. The import must target an empty DB; duplicate IDs indicate the wrong target.
3. Copy `.env.example` to a **new installation's** `.env`. Set DB_DATABASE=krishibyte_manual and this fixture-only key:

```dotenv
APP_ENV=local
APP_KEY=base64:WNUPf/oiv4LJE78xr98t7DfF5VFSTqCuRQDyVPsP9BA=
DB_DATABASE=krishibyte_manual
```

This key is deliberately public and encrypts only synthetic SQL identities. Never use it for production or replace the key of an existing installation. For a fresh database populated through seeders instead of SQL, generate a normal new APP_KEY and the seeder encrypts identities with that key.

4. Copy the generated evidence into the fresh installation's private storage (PowerShell, from the project directory):

```powershell
New-Item -ItemType Directory -Force storage/app/private/evidence
Copy-Item database/sql/private-evidence/evidence/* storage/app/private/evidence/
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

5. Sign in normally at `/login` with the accounts in [Manual testing guide](../../docs/MANUAL_TESTING.md). The showcase page and password-free role shortcuts have been removed.

CLI import alternative, using the installed WAMP MySQL client (replace its path if needed):

```powershell
mysql -u root -p -e "SOURCE D:/KrishiByte/database/sql/schema.sql" krishibyte_manual
mysql -u root -p -e "SOURCE D:/KrishiByte/database/sql/manual_test_data.sql" krishibyte_manual
```

Migrations are included in the data; imported fixtures already match the shipped migration schema. Do not also run `migrate:fresh`. Pending proposals expire after 48 hours; if a snapshot is old, create a new proposal through the normal workspace to test acceptance.

## Framework-table examples

Cache/lock records are expired; the session has no user and is expired; the reset token targets a nonexistent example.test account and is expired. Job/batch/failure rows are labelled synthetic and quarantined in `manual-fixture-disabled`; the queued example is unavailable until 2038. They are table-coverage examples, not a working background task. Do not run/retry that queue or reset fixture. Migrations represent actual installed migrations.

Rebuild using `php tools/build-manual-sql.php` in a clean local build environment. It refuses occupied build/verify databases; it never dumps or resets the working DB. Generated files under storage/manual-fixture-build are temporary build output. The SQL package and all demo seeds are for development/manual tests and should be excluded from production releases.
