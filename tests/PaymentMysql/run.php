<?php

declare(strict_types=1);

namespace App\Tests\PaymentMysql;

use App\Entity\Appointment;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

require __DIR__.'/Support.php';

if ($argc !== 2 || $argv[1] !== '--create-new') {
    fwrite(STDERR, "Usage: php tests/PaymentMysql/run.php --create-new\nCreates a NEW local dedicated database; never reuses or deletes one.\n");
    exit(2);
}

$processes = [];
$artifacts = null;
$exitCode = 0;
try {
    $database = 'appointment_payment_recette_'.gmdate('Ymd').'_'.bin2hex(random_bytes(6));
    $artifacts = sys_get_temp_dir().'/'.$database;
    check(!file_exists($artifacts), 'Artifact directory already exists');
    mkdir($artifacts);
    $server = DriverManager::getConnection(connectionParameters());
    $serverIdentity = identity($server);
    check($serverIdentity['db'] === null, 'Provisioning connection must not select an existing database');
    check((int) $serverIdentity['port'] === 3306 && $serverIdentity['engine'] === 'InnoDB', 'Unexpected local server');
    check(str_starts_with($serverIdentity['version'], '8.'), 'Expected MySQL 8, not an unverified server');
    check($server->fetchOne("SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'InnoDB'") === 'DEFAULT', 'InnoDB unavailable');
    check((int) $server->fetchOne('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]) === 0, 'Refusing to reuse any existing database');
    // Check observer access before creation. No user database is queried.
    $server->fetchOne('SELECT COUNT(*) FROM performance_schema.data_lock_waits WHERE REQUESTING_THREAD_ID = -1');
    $target = ['database' => $database, 'server' => $serverIdentity, 'baseline' => '6e29d1b09cd828c0d37216f792c382456ca55748'];
    writeJson($artifacts.'/target.json', $target);
    echo 'PROVISION: '.json_encode($serverIdentity, JSON_THROW_ON_ERROR)."\nNEW DATABASE: ".$database."\n";
    // The sole provisioning write: a collision fails, never IF NOT EXISTS or DROP.
    // Effective DATABASE() is NULL before creation, then must match exactly below.
    $server->executeStatement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $server->close();
    $connection = guardedConnection($target);
    echo 'TARGET VERIFIED: '.json_encode(identity($connection), JSON_THROW_ON_ERROR)."\n";
    check($connection->createSchemaManager()->listTableNames() === [], 'New database is not empty');
    $em = entityManager($connection);

    // Build the representative PRE-fix schema, never create the final schema.
    // The HEAD mapping differs only by this embedded record/index (checked in Git).
    $schema = (new SchemaTool($em))->getSchemaFromMetadata($em->getMetadataFactory()->getAllMetadata());
    $table = $schema->getTable('appointment');
    $table->dropIndex('uniq_appointment_payment_intent');
    foreach ($table->getColumns() as $column) {
        if (str_starts_with($column->getName(), 'payment_')) {
            $table->dropColumn($column->getName());
        }
    }
    foreach ($schema->getTables() as $schemaTable) {
        $schemaTable->addOption('engine', 'InnoDB');
    }
    $beforeSql = $schema->toSql($connection->getDatabasePlatform());
    file_put_contents($artifacts.'/schema-before.sql', implode(";\n", $beforeSql));
    foreach ($beforeSql as $sql) {
        $connection->executeStatement($sql);
    }
    [$user, $type] = fixtureOwners($em);
    foreach (['pending', 'confirmed', 'canceled'] as $status) {
        $connection->insert('appointment', ['user_id' => $user->getId(), 'type_id' => $type->getId(),
            'start_at' => '2020-01-02 12:00:00', 'end_at' => '2020-01-02 13:30:00',
            'created_at' => '2020-01-01 12:00:00', 'updated_at' => '2020-01-01 12:00:00',
            'status' => $status, 'number' => $status === 'pending' ? null : 'fictitious-legacy-'.$status, 'is_sent' => 0]);
    }
    $before = $connection->fetchAllAssociative('SELECT * FROM appointment ORDER BY id');
    $columnsBefore = array_keys($before[0]);
    check(!in_array('payment_amount', $columnsBefore, true), 'Schema is not pre-fix');

    require_once dirname(__DIR__, 2).'/migrations/Version20261005090000.php';
    $migration = \DoctrineMigrations\Version20261005090000::class;
    // Run the actual Doctrine console executor, metadata storage, diff and DDL.
    foreach ([true, false] as $dryRun) {
        $factory = DependencyFactory::fromConnection(new ConfigurationArray(['migrations' => [$migration]]), new ExistingConnection($connection));
        $tester = new CommandTester(new ExecuteCommand($factory));
        $args = ['versions' => [$migration], '--up' => true];
        if ($dryRun) {
            $args['--dry-run'] = true;
        }
        $code = $tester->execute($args, ['interactive' => false, 'verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG]);
        file_put_contents($artifacts.'/migration-'.($dryRun ? 'dry-run' : 'execute').'.txt', $tester->getDisplay());
        check($code === 0, 'Doctrine migration command failed');
        check(!$connection->isTransactionActive() && !$connection->getNativeConnection()->inTransaction(), 'Migration left an inconsistent transaction');
        if ($dryRun) {
            check(!$connection->createSchemaManager()->introspectTable('appointment')->hasColumn('payment_amount'), 'Dry-run modified appointment');
            check($connection->fetchAllAssociative('SELECT * FROM appointment ORDER BY id') === $before, 'Dry-run changed legacy rows');
        }
    }
    $after = $connection->fetchAllAssociative('SELECT * FROM appointment ORDER BY id');
    check(count($after) === 3, 'Legacy row count changed');
    foreach ($after as $i => $row) {
        check(array_intersect_key($row, $before[$i]) === $before[$i], 'Legacy data changed');
        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'payment_')) {
                check($value === null, 'Historical evidence was invented');
            }
        }
        $loaded = $em->find(Appointment::class, $row['id']);
        check($loaded->getStatus()->value === $row['status'] && $loaded->getNumber() === $row['number'], 'ORM hydration changed legacy state');
        check($loaded->getPayment()->verifiedAt === null && $loaded->getPayableAmount() === 15000, 'ORM legacy fallback failed');
    }
    $columns = $connection->fetchAllAssociative("SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'appointment' AND LEFT(COLUMN_NAME, 8) = 'payment_'", [$database]);
    check(count($columns) === 12 && array_unique(array_column($columns, 'IS_NULLABLE')) === ['YES'], 'Expected 12 nullable columns');
    $index = $connection->createSchemaManager()->introspectTable('appointment')->getIndex('uniq_appointment_payment_intent');
    check($index->isUnique() && $index->getColumns() === ['payment_intent_id'], 'PaymentIntent uniqueness missing');
    $connection->beginTransaction();
    try {
        $connection->executeStatement("UPDATE appointment SET payment_intent_id = 'pi_fictitious_duplicate' WHERE id = ?", [$after[0]['id']]);
        try {
            $connection->executeStatement("UPDATE appointment SET payment_intent_id = 'pi_fictitious_duplicate' WHERE id = ?", [$after[1]['id']]);
            throw new \RuntimeException('Duplicate PaymentIntent accepted');
        } catch (UniqueConstraintViolationException) {
            // Expected real MySQL rejection; rollback both fixture mutations.
        }
    } finally {
        $connection->rollBack();
    }
    check((int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_migration_versions WHERE version = ?', [$migration]) === 1, 'Migration execution not recorded');
    check((int) $connection->fetchOne('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE <> ?', [$database, 'InnoDB']) === 0, 'Non-InnoDB table created');
    echo "MIGRATION OK: actual Doctrine dry-run + execute, 3 legacy rows, 12 nullable columns, unique rejection, ORM hydration.\n";

    $em->clear();
    $summaries = [];
    $start = static function (string $directory, string $name, string $action, int $id, string $hold = 'none') use (&$processes, $artifacts): Process {
        $process = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', __DIR__.'/worker.php', $artifacts.'/target.json', $directory, $name, $action, (string) $id, $hold]);
        $process->setTimeout(35);
        $process->start();
        $processes[] = $process;
        return $process;
    };
    $waitEvent = static function (string $directory, string $worker, string $event): array {
        until(fn () => is_file($directory.'/'.$worker.'.'.$event.'.json'), 'Missing event '.$worker.'/'.$event);
        return readJson($directory.'/'.$worker.'.'.$event.'.json');
    };
    $finish = static function (Process $process): void {
        check($process->wait() === 0, 'Worker failed: '.$process->getErrorOutput());
    };
    $counts = static function (string $directory, string $name): int {
        return is_file($directory.'/'.$name.'.ndjson') ? count(file($directory.'/'.$name.'.ndjson', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
    };
    $observeWait = static function (string $directory) use ($connection, $database, $waitEvent): void {
        $a = $waitEvent($directory, 'a', 'connected');
        $b = $waitEvent($directory, 'b', 'connected');
        check($a['pid'] !== $b['pid'] && $a['connection_id'] !== $b['connection_id'], 'Workers do not use distinct processes/connections');
        $sql = 'SELECT w.REQUESTING_ENGINE_TRANSACTION_ID AS waiting_transaction, w.BLOCKING_ENGINE_TRANSACTION_ID AS blocking_transaction, r.PROCESSLIST_ID AS waiting_connection, b.PROCESSLIST_ID AS blocking_connection, l.OBJECT_SCHEMA AS db, l.OBJECT_NAME AS table_name FROM performance_schema.data_lock_waits w JOIN performance_schema.threads r ON r.THREAD_ID = w.REQUESTING_THREAD_ID JOIN performance_schema.threads b ON b.THREAD_ID = w.BLOCKING_THREAD_ID JOIN performance_schema.data_locks l ON l.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID AND l.ENGINE = w.ENGINE WHERE r.PROCESSLIST_ID = ? AND b.PROCESSLIST_ID = ? AND l.OBJECT_SCHEMA = ? AND l.OBJECT_NAME = ?';
        $observed = false;
        until(function () use ($connection, $sql, $a, $b, $database, &$observed): bool {
            $observed = $connection->fetchAssociative($sql, [$b['connection_id'], $a['connection_id'], $database, 'appointment']);
            return $observed !== false;
        }, 'No real InnoDB lock wait observed');
        writeJson($directory.'/lock-wait.json', $observed);
    };

    foreach (['return-webhook', 'two-webhooks', 'cleanup-first', 'confirmation-first', 'two-notifications'] as $scenario) {
        $directory = $artifacts.'/'.$scenario;
        mkdir($directory);
        [$user, $type] = fixtureOwners($em);
        $appointment = (new Appointment())->setUser($user)->setType($type)
            ->setStartAt(new \DateTimeImmutable('+3 days'))->setEndAt(new \DateTimeImmutable('+3 days +90 minutes'));
        $em->persist($appointment);
        $em->flush();
        [$payments] = services($em, $directory, 'setup');
        check($payments->acceptTerms($appointment), 'Cannot prepare fixture');
        $appointment->getPayment()->intentId = 'pi_fictitious_'.$appointment->getId();
        $appointment->getPayment()->expiresAt = new \DateTimeImmutable(match ($scenario) {
            'cleanup-first' => '-2 seconds', 'confirmation-first' => '+4 seconds', default => '+5 minutes',
        });
        $em->flush();
        $id = $appointment->getId();
        if ($scenario === 'two-notifications') {
            check($payments->confirmWebhook(fakeIntent($appointment)) === 'confirmed', 'Notification fixture not confirmed');
        }
        $firstAction = match ($scenario) { 'return-webhook' => 'return', 'cleanup-first' => 'cleanup', 'two-notifications' => 'notify', default => 'webhook' };
        $secondAction = match ($scenario) { 'confirmation-first' => 'cleanup', 'two-notifications' => 'notify', default => 'webhook' };
        $hold = match ($scenario) { 'confirmation-first' => 'commit', 'two-notifications' => 'lock-mail', default => 'lock' };
        $a = $start($directory, 'a', $firstAction, $id, $hold);
        $waitEvent($directory, 'a', $scenario === 'confirmation-first' ? 'commit-ready' : 'locked');
        if ($scenario === 'confirmation-first') {
            // Confirmation passed its real time check while holding the lock. Wait
            // for actual persisted expiry; cleanup sees the old uncommitted PENDING.
            until(fn () => (bool) $connection->fetchOne('SELECT payment_expires_at <= UTC_TIMESTAMP() FROM appointment WHERE id = ?', [$id]), 'Real deadline not reached');
        }
        $b = $start($directory, 'b', $secondAction, $id);
        $observeWait($directory);
        touch($directory.'/a.release-'.($hold === 'commit' ? 'commit' : 'lock'));
        if ($scenario === 'two-notifications') {
            $waitEvent($directory, 'a', 'transport-ready');
            $finish($b);
            check($counts($directory, 'mail') === 0, 'Second command sent while first was sending');
            check($waitEvent($directory, 'b', 'done')['notification'] === 'sending', 'Second command did not observe sending');
            touch($directory.'/a.release-transport');
        } else {
            $finish($b);
        }
        $finish($a);
        $em->clear();
        $appointment = $em->find(Appointment::class, $id);
        $payment = $appointment->getPayment();
        $late = $scenario === 'cleanup-first';
        check($appointment->getStatus()->value === ($late ? 'canceled' : 'confirmed'), 'Wrong final appointment state');
        check($payment->issue === ($late ? 'late_payment' : null), 'Wrong payment incident');
        check($payment->verifiedAt !== null, 'Missing payment proof');
        check($counts($directory, 'numbers') === ($late ? 0 : 1), 'Number generated more than once or missing');
        check(($appointment->getNumber() === null) === $late, 'Wrong number preservation');
        if ($scenario === 'return-webhook') {
            check($waitEvent($directory, 'a', 'done')['result'] === 'confirmed', 'Browser-return entry failed');
        }
        if ($scenario === 'confirmation-first') {
            check($payment->verifiedAt < $payment->expiresAt, 'Confirmation did not precede actual expiry');
            check(str_contains($waitEvent($directory, 'b', 'command')['output'], '0 rendez-vous annulés'), 'Cleanup did not discard its stale selection');
        }
        if (in_array($scenario, ['two-webhooks', 'confirmation-first'], true)) {
            check($counts($directory, 'mail') === 0 && $payment->notificationState === 'pending', 'Webhook called mail transport');
            $finish($start($directory, 'drain', 'notify', $id));
        }
        $em->refresh($appointment);
        $number = $appointment->getNumber();
        // A later invocation must not repeat a normally completed delivery.
        $finish($start($directory, 'repeat', 'notify', $id));
        $em->refresh($appointment);
        check($appointment->getNumber() === $number, 'Number changed on notification replay');
        check($appointment->getPayment()->notificationState === ($late ? null : 'sent'), 'Wrong durable notification state');
        check($counts($directory, 'mail') === ($late ? 0 : 1), 'Unexpected number of captured sends');
        $summary = ['scenario' => $scenario, 'appointment' => $id, 'status' => $appointment->getStatus()->value,
            'number' => $number, 'issue' => $appointment->getPayment()->issue, 'notification' => $appointment->getPayment()->notificationState,
            'captured_mail' => $counts($directory, 'mail'), 'generated_numbers' => $counts($directory, 'numbers'), 'real_lock_wait' => true];
        $summaries[] = $summary;
        echo 'PASS '.json_encode($summary, JSON_THROW_ON_ERROR)."\n";
    }
    // All original fixture rows remain untouched even after the concurrency tests.
    $legacy = $connection->fetchAllAssociative('SELECT * FROM appointment WHERE id <= ? ORDER BY id', [$after[2]['id']]);
    check($legacy === $after, 'Legacy fixtures changed during concurrency scenarios');
    writeJson($artifacts.'/results.json', ['target' => $target, 'migration' => 'passed', 'scenarios' => $summaries]);
    $connection->close();
    echo "ALL MYSQL CHECKS PASSED\nARTIFACTS: ".$artifacts."\nDatabase retained with fictitious data only; no automatic drop.\n";
} catch (\Throwable $error) {
    // Keep connection parameters/exception traces out of console output.
    fwrite(STDERR, 'FAILED: '.$error::class.' at '.basename($error->getFile()).':'.$error->getLine()."\n");
    if ($error instanceof \Doctrine\DBAL\Exception\DriverException) {
        fwrite(STDERR, 'SQLSTATE: '.$error->getSQLState().' driver code: '.$error->getPrevious()?->getCode()."\n");
    }
    foreach ($error->getTrace() as $frame) {
        if (isset($frame['file']) && str_starts_with($frame['file'], __DIR__)) {
            fwrite(STDERR, basename($frame['file']).':'.($frame['line'] ?? 0)."\n");
        }
    }
    if ($error instanceof \RuntimeException && $error::class === \RuntimeException::class) {
        fwrite(STDERR, $error->getMessage()."\n");
    }
    if ($artifacts !== null) {
        fwrite(STDERR, 'Partial fictitious artifacts/database retained: '.$artifacts."\n");
    }
    $exitCode = 1;
} finally {
    foreach ($processes as $process) {
        if ($process->isRunning()) {
            $process->stop(1);
        }
    }
}
exit($exitCode);
