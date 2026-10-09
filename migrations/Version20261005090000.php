<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist payment evidence, immutable expected amount, consent and notification outbox; no historical backfill.';
    }

    public function isTransactional(): bool
    {
        // MySQL ALTER TABLE / CREATE INDEX commit implicitly. Do not leave DBAL
        // tracking a transaction that the server has already committed.
        return false;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('appointment');
        foreach (['intent_id' => 255, 'currency' => 3, 'attempt_key' => 64, 'issue' => 64,
            'notification_state' => 16, 'notification_error' => 255] as $name => $length) {
            $table->addColumn('payment_'.$name, 'string', ['length' => $length, 'notnull' => false]);
        }
        $table->addColumn('payment_amount', 'integer', ['notnull' => false]);
        foreach (['accepted_at', 'expires_at', 'verified_at', 'notification_attempted_at', 'notification_sent_at'] as $name) {
            $table->addColumn('payment_'.$name, 'datetime_immutable', ['notnull' => false]);
        }
        $table->addUniqueIndex(['payment_intent_id'], 'uniq_appointment_payment_intent');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Payment evidence and notification history must not be discarded. Roll back application code, retaining these nullable columns.');
    }
}
