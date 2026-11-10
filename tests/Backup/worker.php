<?php
// Called only by BackupCommandTest in a fresh PHP interpreter. Never executes a real dump.
require __DIR__.'/DumpFaults.php';
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Tests\Backup\DumpFaults as F;

F::$root = str_replace('\\', '/', realpath($argv[1]));
F::$mode = $argv[2];
F::guard(F::$root);
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite', 'memory' => true, 'dbname' => 'synthetic-db',
    'user' => 'synthetic-user', 'password' => 'synthetic-password',
]);
$tester = new \Symfony\Component\Console\Tester\CommandTester(new \App\Command\DatabaseDumpDataCommand($connection));
$args = ['--output-dir' => F::$root, '--prefix' => 'synthetic', '--binary' => '/never-executed/mysqldump'];
if (F::$mode !== 'sql_only') $args['--gzip'] = true;
if (str_starts_with(F::$mode, 'mkdir_')) $args['--output-dir'] .= '/new-directory';
$code = null;
$exception = null;
try { $code = $tester->execute($args, ['decorated' => false, 'capture_stderr_separately' => false]); }
catch (\Throwable $e) { $exception = $e::class; }
$sql = F::$sql ?? F::$root.'/synthetic_20000101_000000.sql';
$archive = $sql.'.gz';
$content = is_file($sql) ? file_get_contents($sql) : null;
$compressed = is_file($archive) ? file_get_contents($archive) : null;
$temps = [];
foreach (F::$temporaries as $path => $kind) if (file_exists($path)) $temps[] = $kind;
$display = preg_replace('/^\s*! ?/m', '', $tester->getDisplay());
$message = preg_replace('/\s+/u', ' ', trim($display));
foreach (F::$temporaries as $path => $kind) {
    $message = str_replace($path, $kind === 'options_temp' ? '{options}' : '{temporary}', $message);
}
$message = str_replace([$archive, $sql, F::$root], ['{gz}', '{sql}', '{root}'], $message);
// Random option filenames are deliberately normalised, not their error messages.
$message = preg_replace('~\{root\}/mysqldump_\S+~', '{options}', $message);
$message = preg_replace('~\{root\}/\.dump-gzip-\S+~', '{temporary}', $message);
echo json_encode([
    'code' => $code, 'exception' => $exception, 'message' => $message,
    'sql' => $content, 'archive_exists' => $compressed !== null,
    'archive_existing' => $compressed === F::EXISTING,
    'decoded' => $compressed === null ? null : @gzdecode($compressed),
    'temps' => $temps, 'trace' => F::$trace, 'connected' => $connection->isConnected(),
    'unrelated' => file_get_contents(F::$root.'/unrelated.txt'),
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
