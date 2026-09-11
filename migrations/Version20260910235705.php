<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate user.id and mix.id from INT to UUID v7, add slug fields';
    }

    public function up(Schema $schema): void
    {
        // ========================================
        // 1. УДАЛИТЬ ВСЕ FK (пока типы ещё INT)
        // ========================================
        $this->addSql('ALTER TABLE mix DROP FOREIGN KEY FK_55AFA881A76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA6013C4A');

        // ========================================
        // 2. УДАЛИТЬ СТАРЫЙ mix.uuid и его индекс
        // ========================================
        $this->addSql('DROP INDEX UNIQ_55AFA881D17F50A6 ON mix');
        $this->addSql('ALTER TABLE mix DROP uuid');

        // ========================================
        // 3. ИЗМЕНИТЬ USER.id на BINARY(16)
        // ========================================
        $this->addSql('ALTER TABLE user CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE user ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649989D9B62 ON user (slug)');

        // ========================================
        // 4. ИЗМЕНИТЬ MIX.id и MIX.user_id на BINARY(16)
        // ========================================
        $this->addSql('ALTER TABLE mix CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE mix CHANGE user_id user_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE mix ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55AFA881989D9B62 ON mix (slug)');

        // ========================================
        // 5. ИЗМЕНИТЬ user_favorite_mix
        // ========================================
        $this->addSql('ALTER TABLE user_favorite_mix CHANGE user_id user_id BINARY(16) NOT NULL, CHANGE mix_id mix_id BINARY(16) NOT NULL');

        // ========================================
        // 6. ВОССТАНОВИТЬ FK
        // ========================================
        $this->addSql('ALTER TABLE mix ADD CONSTRAINT FK_55AFA881A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA6013C4A FOREIGN KEY (mix_id) REFERENCES mix (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Откат (если нужно)
        $this->addSql('ALTER TABLE mix DROP FOREIGN KEY FK_55AFA881A76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA76ED395');
        $this->addSql('ALTER TABLE user_favorite_mix DROP FOREIGN KEY FK_BEB9C90AA6013C4A');

        $this->addSql('DROP INDEX UNIQ_8D93D649989D9B62 ON user');
        $this->addSql('ALTER TABLE user DROP slug');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');

        $this->addSql('DROP INDEX UNIQ_55AFA881989D9B62 ON mix');
        $this->addSql('ALTER TABLE mix DROP slug');
        $this->addSql('ALTER TABLE mix CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE mix CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE mix ADD uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55AFA881D17F50A6 ON mix (uuid)');

        $this->addSql('ALTER TABLE user_favorite_mix CHANGE user_id user_id INT NOT NULL, CHANGE mix_id mix_id INT NOT NULL');

        $this->addSql('ALTER TABLE mix ADD CONSTRAINT FK_55AFA881A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_mix ADD CONSTRAINT FK_BEB9C90AA6013C4A FOREIGN KEY (mix_id) REFERENCES mix (id) ON DELETE CASCADE');
    }
}
