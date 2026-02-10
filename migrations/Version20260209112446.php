<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209112446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, duration INT NOT NULL, min_age INT DEFAULT NULL, max_age INT DEFAULT NULL, price INT NOT NULL, participants INT NOT NULL, is_pack TINYINT NOT NULL, description LONGTEXT DEFAULT NULL, introduction LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, prerequisite_id INT DEFAULT NULL, INDEX IDX_9F1D9FE8276AF86B (prerequisite_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment_type ADD CONSTRAINT FK_9F1D9FE8276AF86B FOREIGN KEY (prerequisite_id) REFERENCES appointment_type (id)');
        $this->addSql('ALTER TABLE avatar ADD CONSTRAINT FK_1677722FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE link ADD CONSTRAINT FK_36AC99F1D087DB59 FOREIGN KEY (about_id) REFERENCES about (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_type DROP FOREIGN KEY FK_9F1D9FE8276AF86B');
        $this->addSql('DROP TABLE appointment_type');
        $this->addSql('ALTER TABLE avatar DROP FOREIGN KEY FK_1677722FA76ED395');
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F1D087DB59');
    }
}
