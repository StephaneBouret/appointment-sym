<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209114739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, number VARCHAR(191) DEFAULT NULL, is_sent TINYINT DEFAULT 0 NOT NULL, reminder7_sent_at DATETIME DEFAULT NULL, reminder24_sent_at DATETIME DEFAULT NULL, visio_url VARCHAR(255) DEFAULT NULL, evaluated_person_firstname VARCHAR(100) DEFAULT NULL, evaluated_person_lastname VARCHAR(100) DEFAULT NULL, evaluated_person_patronyms VARCHAR(255) DEFAULT NULL, evaluated_person_birthdate DATE DEFAULT NULL, partner_firstname VARCHAR(100) DEFAULT NULL, partner_lastname VARCHAR(100) DEFAULT NULL, partner_patronyms VARCHAR(255) DEFAULT NULL, partner_birthdate DATE DEFAULT NULL, user_id INT NOT NULL, type_id INT NOT NULL, UNIQUE INDEX UNIQ_FE38F84496901F54 (number), INDEX IDX_FE38F844A76ED395 (user_id), INDEX IDX_FE38F844C54C8C93 (type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844C54C8C93 FOREIGN KEY (type_id) REFERENCES appointment_type (id)');
        $this->addSql('ALTER TABLE appointment_type ADD CONSTRAINT FK_9F1D9FE8276AF86B FOREIGN KEY (prerequisite_id) REFERENCES appointment_type (id)');
        $this->addSql('ALTER TABLE avatar ADD CONSTRAINT FK_1677722FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE link ADD CONSTRAINT FK_36AC99F1D087DB59 FOREIGN KEY (about_id) REFERENCES about (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844A76ED395');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844C54C8C93');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('ALTER TABLE appointment_type DROP FOREIGN KEY FK_9F1D9FE8276AF86B');
        $this->addSql('ALTER TABLE avatar DROP FOREIGN KEY FK_1677722FA76ED395');
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F1D087DB59');
    }
}
