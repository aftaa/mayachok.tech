<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260731182320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mix ADD uuid VARCHAR(36) NOT NULL, ADD original_size INT DEFAULT NULL, ADD mp3_size INT DEFAULT NULL, ADD peaks_size INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55AFA881D17F50A6 ON mix (uuid)');
        $this->addSql('ALTER TABLE user ADD storage_used BIGINT NOT NULL, ADD storage_limit BIGINT NOT NULL');
        $this->addSql('ALTER TABLE user_favorite_mix RENAME INDEX idx_beb9c90aa76ed395 TO IDX_DD1BC781A76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix RENAME INDEX idx_beb9c90aa6013c4a TO IDX_DD1BC781A6013C4A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_55AFA881D17F50A6 ON mix');
        $this->addSql('ALTER TABLE mix DROP uuid, DROP original_size, DROP mp3_size, DROP peaks_size');
        $this->addSql('ALTER TABLE user DROP storage_used, DROP storage_limit');
        $this->addSql('ALTER TABLE user_favorite_mix RENAME INDEX idx_dd1bc781a76ed395 TO IDX_BEB9C90AA76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix RENAME INDEX idx_dd1bc781a6013c4a TO IDX_BEB9C90AA6013C4A');
    }
}
