<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260909144257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mix ADD status VARCHAR(20) DEFAULT \'pending\' NOT NULL, ADD created_at DATETIME NOT NULL, ADD processed_at DATETIME DEFAULT NULL, DROP is_processed');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mix ADD is_processed TINYINT NOT NULL, DROP status, DROP created_at, DROP processed_at');
    }
}
