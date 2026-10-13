<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Local-only backup/restore verification utility; never exposed through a web route.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$pdo = DB::connection()->getPdo();
$backupDate = $argv[1] ?? '20261012';
if (! preg_match('/^\d{8}$/', $backupDate)) {
    throw new RuntimeException('Use an eight-digit backup date.');
}
$directory = 'D:/KrishiByte-security-backup-'.$backupDate;
$restoreName = 'krishibyte_security_restore_'.$backupDate;
if ($pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '.$pdo->quote($restoreName))->fetchColumn() > 0) {
    throw new RuntimeException('Restore database must be empty; existing backups are preserved.');
}
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}
if (file_exists($directory.'/database.json')) {
    throw new RuntimeException('Existing backup snapshot will not be overwritten.');
}
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$pdo->beginTransaction();
$snapshot = [];
foreach ($tables as $table) {
    $snapshot[$table] = ['schema' => $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1], 'rows' => $pdo->query("SELECT * FROM `$table` ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC)];
}
$pdo->commit();
file_put_contents($directory.'/database.json', json_encode($snapshot, JSON_THROW_ON_ERROR));
copy(base_path('.env'), $directory.'/local-environment.private');
if (is_dir(storage_path('app/private/evidence'))) {
    File::copyDirectory(storage_path('app/private/evidence'), $directory.'/evidence');
}
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$restoreName`");
$pdo->exec("USE `$restoreName`");
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($snapshot as $table => $data) {
    if ($pdo->query('SHOW TABLES LIKE '.$pdo->quote($table))->fetchColumn()) {
        throw new RuntimeException('Restore database must be empty.');
    }
    $pdo->exec($data['schema']);
    foreach ($data['rows'] as $row) {
        $columns = implode(',', array_map(fn ($key) => "`$key`", array_keys($row)));
        $values = implode(',', array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row)));
        $pdo->exec("INSERT INTO `$table` ($columns) VALUES ($values)");
    }
    $restored = $pdo->query("SELECT * FROM `$table` ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC);
    if ($restored !== $data['rows']) {
        throw new RuntimeException('Restore comparison failed.');
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo 'Private backup created; isolated restore matched all '.count($snapshot)." tables.\n";
