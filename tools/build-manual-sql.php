<?php

use Database\Seeders\ManualTestingSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Build a synthetic export in isolated databases; never dump the working application's data.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$fixtureDatabase = 'krishibyte_fixture_build_20261013';
$restoreDatabase = 'krishibyte_fixture_verify_20261013';
$fixtureKey = 'base64:'.base64_encode(hash('sha256', 'KrishiByte public synthetic SQL fixture key', true));
foreach (['APP_ENV' => 'testing', 'DB_DATABASE' => $fixtureDatabase, 'APP_KEY' => $fixtureKey, 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
    throw new RuntimeException('Clear cached configuration before building isolated fixtures.');
}
$app = require __DIR__.'/../bootstrap/app.php';
$fixtureStorage = __DIR__.'/../storage/manual-fixture-build';
foreach (['logs', 'framework/cache/data', 'framework/sessions', 'framework/views', 'app/private', 'app/public'] as $folder) {
    if (! is_dir($fixtureStorage.'/'.$folder)) {
        mkdir($fixtureStorage.'/'.$folder, 0750, true);
    }
}
$app->useStoragePath($fixtureStorage);
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$config = config('database.connections.mysql');
if ($config['database'] !== $fixtureDatabase) {
    throw new RuntimeException('Fixture database isolation failed.');
}
$server = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4', $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach ([$fixtureDatabase, $restoreDatabase] as $database) {
    $count = $server->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='.$server->quote($database))->fetchColumn();
    if ($count > 0) {
        throw new RuntimeException('Existing fixture databases are preserved; use a clean isolated build environment.');
    }
    $server->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
if ($kernel->call('migrate', ['--force' => true]) !== 0 || $kernel->call('db:seed', ['--class' => ManualTestingSeeder::class, '--force' => true]) !== 0) {
    throw new RuntimeException($kernel->output());
}
$pdo = DB::connection()->getPdo();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schema = "-- KrishiByte schema. Import into a NEW EMPTY database; no DROP/USE statements.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
$data = "-- SYNTHETIC MANUAL TEST DATA ONLY. Never import into a live/occupied database.\n-- Includes expired/quarantined framework examples; see README.md in this folder.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n";
$manifest = ['synthetic_only' => true, 'fixture_app_key' => $fixtureKey, 'tables' => []];
$snapshots = [];
foreach ($tables as $table) {
    $schema .= $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1].";\n\n";
    $rows = $pdo->query("SELECT * FROM `$table` ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC);
    if (! $rows) {
        throw new RuntimeException('Table coverage missing: '.$table);
    }
    $snapshots[$table] = $rows;
    $manifest['tables'][$table] = count($rows);
    foreach ($rows as $row) {
        $columns = implode(',', array_map(fn ($column) => '`'.$column.'`', array_keys($row)));
        $values = implode(',', array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row)));
        $data .= "INSERT INTO `$table` ($columns) VALUES ($values);\n";
    }
}
$schema .= "SET FOREIGN_KEY_CHECKS=1;\n";
$data .= "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n";
$destination = __DIR__.'/../database/sql';
if (! is_dir($destination)) {
    mkdir($destination, 0750, true);
}
file_put_contents($destination.'/schema.sql', $schema);
file_put_contents($destination.'/manual_test_data.sql', $data);
$server->exec("USE `$restoreDatabase`");
$server->exec($schema);
$server->exec($data);
foreach ($snapshots as $table => $rows) {
    if ($server->query("SELECT * FROM `$table` ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC) !== $rows) {
        throw new RuntimeException('SQL round-trip comparison failed: '.$table);
    }
}
$manifest['restore_verified'] = true;
$manifest['schema_sha256'] = hash('sha256', $schema);
$manifest['data_sha256'] = hash('sha256', $data);
$manifest['evidence_files'] = [];
foreach (Storage::disk('local')->allFiles('evidence') as $path) {
    $target = $destination.'/private-evidence/'.$path;
    if (! is_dir(dirname($target))) {
        mkdir(dirname($target), 0750, true);
    }
    $bytes = Storage::disk('local')->get($path);
    file_put_contents($target, $bytes);
    $manifest['evidence_files'][$path] = hash('sha256', $bytes);
}
file_put_contents($destination.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
echo 'Synthetic schema/data exports verified: '.count($tables).' populated tables, '.count($manifest['evidence_files'])." generated private fixtures.\n";
