<?php

namespace App\Tests\Payment;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;

final class PaymentMigrationTest extends PaymentTestCase
{
    public function testMigrationPreservesLegacyRowsWithoutInventingEvidence(): void
    {
        require_once dirname(__DIR__, 2).'/migrations/Version20261005090000.php';
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        self::assertTrue($connection->getParams()['memory']);
        $connection->executeStatement('CREATE TABLE appointment (id INTEGER PRIMARY KEY, status VARCHAR(16), number VARCHAR(191))');
        $connection->insert('appointment', ['id' => 1, 'status' => 'confirmed', 'number' => 'legacy-manual']);
        $connection->insert('appointment', ['id' => 2, 'status' => 'pending', 'number' => null]);
        $manager = $connection->createSchemaManager();
        $before = $manager->introspectSchema();
        $after = clone $before;
        $migration = new \DoctrineMigrations\Version20261005090000($connection, new NullLogger());
        self::assertFalse($migration->isTransactional());
        $migration->up($after);
        $diff = $manager->createComparator()->compareSchemas($before, $after);
        foreach ($connection->getDatabasePlatform()->getAlterSchemaSQL($diff) as $sql) {
            $connection->executeStatement($sql);
        }
        $rows = $connection->fetchAllAssociative('SELECT * FROM appointment ORDER BY id');
        self::assertSame('confirmed', $rows[0]['status']);
        self::assertSame('legacy-manual', $rows[0]['number']);
        self::assertSame('pending', $rows[1]['status']);
        foreach ($rows as $row) {
            foreach ($row as $name => $value) {
                if (str_starts_with($name, 'payment_')) {
                    self::assertNull($value);
                }
            }
        }
        $ormTable = (new SchemaTool($this->em))->getSchemaFromMetadata($this->em->getMetadataFactory()->getAllMetadata())->getTable('appointment');
        foreach ($ormTable->getColumns() as $column) {
            if (str_starts_with($column->getName(), 'payment_')) {
                self::assertTrue($after->getTable('appointment')->hasColumn($column->getName()));
            }
        }
        self::assertTrue($after->getTable('appointment')->hasIndex('uniq_appointment_payment_intent'));
        $connection->close();
    }
}
