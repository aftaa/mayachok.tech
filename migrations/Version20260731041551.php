<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260731041551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_favorite_mix (user_id INT NOT NULL, mix_id INT NOT NULL, INDEX IDX_BEB9C90AA76ED395 (user_id), INDEX IDX_BEB9C90AA6013C4A (mix_id), PRIMARY KEY (user_id, mix_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA6013C4A FOREIGN KEY (mix_id) REFERENCES mix (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA6013C4A');
        $this->addSql('DROP TABLE user_favorite_mix');
    }
}
