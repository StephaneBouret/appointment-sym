<?php
namespace App\Tests\Backup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BackupCommandTest extends TestCase
{
    private static string $suiteRoot;

    public static function setUpBeforeClass(): void
    {
        // One fresh directory for the entire suite; never visit application backups/uploads.
        self::$suiteRoot = sys_get_temp_dir().'/l9-'.bin2hex(random_bytes(4));
        mkdir(self::$suiteRoot, 0700);
    }

    public static function tearDownAfterClass(): void
    {
        // Delete only our private tree, without following links.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$suiteRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir() && !$file->isLink()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir(self::$suiteRoot);
    }

    public static function scenarios(): iterable
    {
        yield 'SQL seul' => ['sql_only', null, true, false];
        yield 'gzip conforme (diagnostic)' => ['success', null, false, true];
        yield 'SQL vide valide' => ['empty_sql', null, false, true];
        $stages = [
            'read' => 'Impossible de lire le SQL ; SQL conservé : {sql}',
            'compress' => 'Compression gzip échouée ; SQL conservé : {sql}',
            'archive_write' => 'Écriture gzip incomplète ; SQL conservé : {sql}',
            'verify_read' => 'Vérification gzip échouée ; SQL conservé : {sql}',
            'decode' => 'Vérification gzip échouée ; SQL conservé : {sql}',
            'archive_temp' => 'Impossible de créer le temporaire gzip dans le dossier de destination ; SQL conservé : {sql}',
            'finalize' => "Impossible de finaliser l'archive sans écrasement ; SQL conservé : {sql}",
        ];
        foreach ($stages as $operation => $message) {
            foreach (['false', 'throw'] as $fault) yield $operation.' '.$fault => [$operation.'_'.$fault, $message, true, false];
        }
        foreach (['zero', 'partial'] as $fault) yield 'archive_write '.$fault => ['archive_write_'.$fault, $stages['archive_write'], true, false];
        foreach (['corrupt_write', 'corrupt_compress'] as $fault) yield $fault => [$fault, $stages['decode'], true, false];
        yield 'tempnam hors destination' => ['temp_fallback', $stages['archive_temp'], true, false];
        foreach (['no_zlib', 'no_decode'] as $mode) yield $mode => [$mode, 'zlib indisponible ; dump SQL conservé, aucune archive créée : {sql}', true, false];
        yield 'processus échec (diagnostic)' => ['process_false', 'mysqldump a échoué ; SQL non validé conservé : {sql}', true, false];
        foreach (['process', 'process_construct'] as $op) yield $op.' exception' => [$op.'_throw', 'Exception du processus mysqldump ; SQL non validé conservé : {sql}', true, false];
        foreach ([
            'options_temp' => "Impossible de créer le fichier d'options mysqldump",
            'chmod' => "Impossible de protéger le fichier d'options mysqldump",
            'options_write' => "Impossible d'écrire complètement le fichier d'options mysqldump",
            'reserve' => 'Impossible de réserver le fichier SQL sans écrasement : {sql}',
            'mkdir' => 'Impossible de créer le dossier : {root}/new-directory',
        ] as $op => $message) {
            foreach (['false', 'throw'] as $fault) yield $op.' '.$fault => [$op.'_'.$fault, $message, false, false];
        }
        foreach (['zero', 'partial'] as $fault) yield 'options_write '.$fault => ['options_write_'.$fault, "Impossible d'écrire complètement le fichier d'options mysqldump", false, false];
        foreach (['false', 'throw'] as $fault) yield 'close '.$fault => ['close_'.$fault, 'Impossible de fermer le fichier SQL réservé : {sql}', true, false];
        yield 'collision SQL' => ['sql_collision', 'Destination déjà existante : {sql}', true, false];
        yield 'collision répertoire SQL' => ['sql_directory', 'Destination déjà existante : {sql}', false, false];
        yield 'collision gzip' => ['archive_collision', 'Destination déjà existante : {gz}', false, true];
        yield 'collision SQL concurrente' => ['reserve_race', 'Impossible de réserver le fichier SQL sans écrasement : {sql}', true, false];
        yield 'collision gzip concurrente' => ['finalize_race', $stages['finalize'], true, true];
        foreach (['false', 'throw'] as $fault) {
            yield 'suppression SQL '.$fault => ['sql_unlink_'.$fault, 'Archive valide créée : {gz} ; suppression du SQL impossible : {sql}', true, true];
            yield 'nettoyage gzip '.$fault => ['archive_unlink_'.$fault, 'Archive valide créée : {gz} ; suppression du temporaire gzip impossible ; SQL conservé : {sql}', true, true];
            yield 'nettoyage options '.$fault => ['options_unlink_'.$fault, "Nettoyage du fichier d'options mysqldump impossible ; SQL conservé : {sql}", true, false];
        }
        yield 'erreur initiale et nettoyage' => ['process_cleanup_throw', 'Exception du processus mysqldump ; SQL non validé conservé : {sql}', true, false];
    }

    #[DataProvider('scenarios')]
    public function testCommand(string $mode, ?string $error, bool $sqlExists, bool $archiveExists): void
    {
        $root = self::$suiteRoot.'/'.substr(hash('sha256', $mode), 0, 8);
        mkdir($root, 0700);
        file_put_contents($root.'/unrelated.txt', 'unrelated synthetic content');
        // Only this PHP worker can run. Its Process class cannot launch any executable.
        $process = proc_open([PHP_BINARY, '-d', 'xdebug.mode=off', __DIR__.'/worker.php', $root, $mode],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr);
        self::assertSame('', $stderr);
        $result = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        $success = in_array($mode, ['success', 'sql_only', 'empty_sql'], true);
        self::assertNull($result['exception'], json_encode($result));
        self::assertSame($success ? 0 : 1, $result['code'], json_encode($result));
        self::assertFalse($result['connected']);
        self::assertSame('unrelated synthetic content', $result['unrelated']);
        self::assertSame($sqlExists, $result['sql'] !== null);
        self::assertSame($archiveExists, $result['archive_exists']);
        if ($sqlExists) {
            $expected = in_array($mode, ['sql_collision', 'reserve_race'], true)
                ? 'preexisting synthetic file: never overwrite'
                : (in_array($mode, ['close_false', 'close_throw', 'process_throw', 'process_construct_throw', 'process_cleanup_throw'], true) ? ''
                    : "-- synthetic only, échantillon\nINSERT INTO example VALUES (1, 'été');\n");
            self::assertSame($expected, $result['sql']);
        }
        if ($archiveExists) {
            if (in_array($mode, ['archive_collision', 'finalize_race'], true)) self::assertTrue($result['archive_existing']);
            else self::assertSame($mode === 'empty_sql' ? '' : "-- synthetic only, échantillon\nINSERT INTO example VALUES (1, 'été');\n", $result['decoded']);
        }
        $expectedTemps = [];
        if (str_starts_with($mode, 'options_unlink_') || $mode === 'process_cleanup_throw') $expectedTemps[] = 'options_temp';
        if (str_starts_with($mode, 'archive_unlink_')) $expectedTemps[] = 'archive_temp';
        self::assertSame($expectedTemps, $result['temps']);
        if (!$success && !str_starts_with($mode, 'sql_unlink_')) {
            self::assertNotContains('sql_unlink', $result['trace'], 'SQL must survive failure');
        }
        foreach (['synthetic-password', 'synthetic-user', 'synthetic-db'] as $secret) self::assertStringNotContainsString($secret, $result['message']);

        $expectedMessage = in_array('process_construct', $result['trace'], true) ? 'Exécution de mysqldump ' : '';
        if ($error !== null) $expectedMessage .= '[ERROR] '.$error.' ';
        if ($mode === 'close_throw') $expectedMessage .= '[ERROR] Fermeture du fichier SQL réservé impossible ';
        if (str_starts_with($mode, 'options_unlink_') || $mode === 'process_cleanup_throw') $expectedMessage .= "[ERROR] Nettoyage impossible du fichier d'options mysqldump : {options} ";
        if (str_starts_with($mode, 'archive_unlink_')) $expectedMessage .= '[ERROR] Nettoyage impossible du temporaire gzip : {temporary} ';
        if ($success) $expectedMessage .= '[OK] Dump terminé: '.($mode === 'sql_only' ? '{sql}' : '{gz}');
        elseif (in_array('read', $result['trace'], true) || in_array($mode, ['no_zlib', 'no_decode', 'options_unlink_false', 'options_unlink_throw'], true)) {
            $expectedMessage .= '[NOTE] Fichier produit conservé : '.($archiveExists && $mode !== 'finalize_race' ? '{gz}' : '{sql}');
        }
        self::assertSame(trim($expectedMessage), $result['message']);
    }
}
