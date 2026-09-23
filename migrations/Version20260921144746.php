<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921144746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE music_request (id INT AUTO_INCREMENT NOT NULL, donation_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, artiste VARCHAR(255) NOT NULL, youtube_url VARCHAR(255) DEFAULT NULL, votes INT NOT NULL, status VARCHAR(20) NOT NULL, validation_type VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', validated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_FF631DE44DC1279C (donation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE music_request_vote (id INT AUTO_INCREMENT NOT NULL, request_id INT NOT NULL, token VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A1A79329427EB8A5 (request_id), UNIQUE INDEX unique_music_request_vote (request_id, token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE music_request ADD CONSTRAINT FK_FF631DE44DC1279C FOREIGN KEY (donation_id) REFERENCES donation (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE music_request_vote ADD CONSTRAINT FK_A1A79329427EB8A5 FOREIGN KEY (request_id) REFERENCES music_request (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE music_request DROP FOREIGN KEY FK_FF631DE44DC1279C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE music_request_vote DROP FOREIGN KEY FK_A1A79329427EB8A5
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE music_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE music_request_vote
        SQL);
    }
}
