<?php

// Explicit local maintenance helper. Never invoked by the application or test suite.
namespace App\Tests\Slots;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

const PAYMENT_VERSION = 'DoctrineMigrations\\Version20261005090000';

function requireLocal(bool $condition): void
{
    if (!$condition) { throw new \RuntimeException('Local migration guard failed'); }
}

function target(\Doctrine\DBAL\Connection $db): array
{
    $actual = $db->fetchAssociative('SELECT DATABASE() AS db, @@hostname AS server, @@server_uuid AS uuid, @@port AS port, VERSION() AS version, CONNECTION_ID() AS connection_id');
    $params = $db->getParams();
    $actual['configured_host'] = $params['host'] ?? null;
    requireLocal($actual['db'] === 'appointment_sym' && $actual['server'] === 'STEF-LEGION'
        && $actual['configured_host'] === '127.0.0.1' && (int) $actual['port'] === 3306
        && $actual['uuid'] === '7333eaf6-c92e-11f0-9720-183d2df16ad8');
    $engines = $db->fetchAllKeyValue("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('appointment','doctrine_migration_versions')");
    requireLocal(count($engines) === 2 && count(array_filter($engines, fn ($e) => $e !== 'InnoDB')) === 0);
    $actual['engines'] = $engines;
    return $actual;
}

function capture(\Doctrine\DBAL\Connection $db, ?array $legacyColumns = null): array
{
    $db->executeStatement('SET TRANSACTION READ ONLY');
    $db->beginTransaction();
    try {
        $actual = target($db);
        $columns = $db->fetchAllAssociative("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='appointment' ORDER BY ORDINAL_POSITION");
        $legacyColumns ??= array_column($columns, 'COLUMN_NAME');
        foreach ($legacyColumns as $name) { requireLocal((bool) preg_match('/^[a-z_][a-z0-9_]*$/D', $name)); }
        $names = implode(', ', array_map($db->quoteIdentifier(...), $legacyColumns));
        $rows = $db->fetchAllAssociative('SELECT '.$names.' FROM appointment ORDER BY id');
        $amounts = $db->fetchAllAssociative('SELECT a.id, a.type_id, t.price FROM appointment a JOIN appointment_type t ON t.id=a.type_id ORDER BY a.id');
        $versions = $db->fetchAllAssociative('SELECT * FROM doctrine_migration_versions ORDER BY version');
        return ['target' => $actual, 'legacy_columns' => $legacyColumns, 'appointment_count' => count($rows),
            'legacy_rows_sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)),
            'historical_amounts_sha256' => hash('sha256', json_encode($amounts, JSON_THROW_ON_ERROR)),
            'catalog_prices_sha256' => hash('sha256', json_encode($db->fetchAllAssociative('SELECT id, price FROM appointment_type ORDER BY id'), JSON_THROW_ON_ERROR)),
            'status_counts' => $db->fetchAllAssociative('SELECT status, COUNT(*) AS count FROM appointment GROUP BY status ORDER BY status'),
            'version_rows' => $versions,
            'payment_columns' => array_values(array_filter($columns, fn ($c) => str_starts_with($c['COLUMN_NAME'], 'payment_'))),
            'payment_index' => $db->fetchAllAssociative("SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='appointment' AND INDEX_NAME='uniq_appointment_payment_intent'")];
    } finally { $db->rollBack(); }
}

function printSafe(array $snapshot): void
{
    echo json_encode(['target' => $snapshot['target'], 'appointment_count' => $snapshot['appointment_count'],
        'payment_columns' => count($snapshot['payment_columns']),
        'payment_version_count' => count(array_filter($snapshot['version_rows'], fn ($r) => $r['version'] === PAYMENT_VERSION))], JSON_THROW_ON_ERROR)."\n";
}

// HTTP probe includes only the guarded functions above.
if (PHP_SAPI !== 'cli' || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
try {
    (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');
    requireLocal($_SERVER['APP_ENV'] === 'dev');
    $kernel = new \App\Kernel('dev', (bool) $_SERVER['APP_DEBUG']);
    $kernel->boot();
    $db = $kernel->getContainer()->get('doctrine')->getConnection();
    $action = $argv[1] ?? 'target';
    if ($action === 'target') { printSafe(capture($db)); exit(0); }
    $directory = realpath($argv[2] ?? '');
    requireLocal(is_string($directory) && str_starts_with(strtolower($directory), strtolower(getenv('USERPROFILE').'\\appointment-sym-backups\\')));
    $baselinePath = $directory.'/before.json';
    if ($action === 'backup') {
        requireLocal(!file_exists($baselinePath) && !file_exists($directory.'/appointment-before-payment.sql'));
        $before = capture($db);
        requireLocal(count($before['payment_columns']) === 0 && count(array_filter($before['version_rows'], fn ($r) => $r['version'] === PAYMENT_VERSION)) === 0);
        printSafe($before);
        file_put_contents($baselinePath, json_encode($before, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        $params = $db->getParams();
        $escape = fn ($v) => '"'.strtr((string) $v, ["\\" => "\\\\", '"' => '\\"', "\n" => '\\n', "\r" => '\\r']).'"';
        $optionFile = $directory.'/mysql-private.cnf';
        file_put_contents($optionFile, "[client]\nhost=127.0.0.1\nport=3306\nprotocol=tcp\nuser=".$escape($params['user'])."\npassword=".$escape($params['password'] ?? '')."\n");
        $dumpPath = $directory.'/appointment-before-payment.sql';
        try {
            $command = ['C:/wamp64/bin/mysql/mysql8.4.7/bin/mysqldump.exe', '--defaults-extra-file='.$optionFile,
                '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--set-gtid-purged=OFF',
                '--hex-blob', '--skip-extended-insert', '--complete-insert', '--default-character-set=utf8mb4',
                '--result-file='.$dumpPath, 'appointment_sym', 'appointment', 'doctrine_migration_versions'];
            $process = proc_open($command, [1 => ['file', $directory.'/dump-private-output.txt', 'w'], 2 => ['file', $directory.'/dump-private-errors.txt', 'w']], $pipes);
            requireLocal(is_resource($process));
            $exit = proc_close($process);
            requireLocal($exit === 0);
        } finally { if (is_file($optionFile)) { unlink($optionFile); } }
        $dump = file_get_contents($dumpPath);
        requireLocal(str_contains($dump, '-- Dump completed on '));
        preg_match_all('/^CREATE TABLE `([^`]+)`/m', $dump, $tables);
        requireLocal($tables[1] === ['appointment', 'doctrine_migration_versions']);
        $rowCounts = [];
        foreach (['appointment' => $before['appointment_count'], 'doctrine_migration_versions' => count($before['version_rows'])] as $table => $expected) {
            $rowCounts[$table] = preg_match_all('/^INSERT INTO `'.$table.'` /m', $dump);
            requireLocal($rowCounts[$table] === $expected);
        }
        $afterDump = capture($db, $before['legacy_columns']);
        foreach (['legacy_rows_sha256', 'historical_amounts_sha256', 'catalog_prices_sha256', 'version_rows'] as $key) { requireLocal($afterDump[$key] === $before[$key]); }
        $manifest = ['database' => 'appointment_sym', 'dump_file' => basename($dumpPath), 'bytes' => filesize($dumpPath),
            'sha256' => hash_file('sha256', $dumpPath), 'dump_exit_code' => $exit, 'tables' => $tables[1],
            'row_counts_verified' => $rowCounts, 'completion_marker' => true, 'stable_during_backup' => true];
        file_put_contents($directory.'/backup-verified.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        echo json_encode($manifest, JSON_THROW_ON_ERROR)."\n";
    } elseif ($action === 'verify') {
        $before = json_decode(file_get_contents($baselinePath), true, flags: JSON_THROW_ON_ERROR);
        $after = capture($db, $before['legacy_columns']);
        printSafe($after);
        foreach (['appointment_count', 'legacy_rows_sha256', 'historical_amounts_sha256', 'catalog_prices_sha256', 'status_counts'] as $key) { requireLocal($after[$key] === $before[$key]); }
        $expected = ['intent_id', 'currency', 'attempt_key', 'issue', 'notification_state', 'notification_error', 'amount',
            'accepted_at', 'expires_at', 'verified_at', 'notification_attempted_at', 'notification_sent_at'];
        $expected = array_map(fn ($n) => 'payment_'.$n, $expected); sort($expected);
        $actual = array_column($after['payment_columns'], 'COLUMN_NAME'); sort($actual);
        requireLocal($actual === $expected && count(array_filter($after['payment_columns'], fn ($c) => $c['IS_NULLABLE'] !== 'YES')) === 0);
        requireLocal(count($after['payment_index']) === 1 && (int) $after['payment_index'][0]['NON_UNIQUE'] === 0 && $after['payment_index'][0]['COLUMN_NAME'] === 'payment_intent_id');
        $oldVersions = array_values(array_filter($after['version_rows'], fn ($r) => $r['version'] !== PAYMENT_VERSION));
        requireLocal($oldVersions === $before['version_rows'] && count($after['version_rows']) === count($before['version_rows']) + 1);
        $nonNull = implode(' OR ', array_map(fn ($name) => $db->quoteIdentifier($name).' IS NOT NULL', $expected));
        requireLocal((int) $db->fetchOne('SELECT COUNT(*) FROM appointment WHERE '.$nonNull) === 0);
        $result = ['verified' => true, 'appointment_count_preserved' => $after['appointment_count'], 'all_legacy_columns_unchanged' => true,
            'statuses_numbers_and_historical_amounts_unchanged' => true, 'only_authorized_version_added' => true,
            'twelve_nullable_columns' => true, 'unique_payment_intent_index' => true, 'historical_payment_columns_all_null' => true];
        file_put_contents($directory.'/after-verified.json', json_encode(['result' => $result, 'snapshot' => $after], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
    } else { throw new \RuntimeException('Unsupported action'); }
} catch (\Throwable $error) {
    fwrite(STDERR, $error::class.' at '.basename($error->getFile()).':'.$error->getLine()."\n"); exit(1);
}
