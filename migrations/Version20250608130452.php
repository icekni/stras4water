<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608130452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE hello_asso_token (id INT NOT NULL, access_token VARCHAR(255) NOT NULL, access_token_expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', refresh_token VARCHAR(255) NOT NULL, refresh_token_expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql("INSERT INTO hello_asso_token (id, access_token, access_token_expires_at, refresh_token, refresh_token_expires_at) VALUES (1, '', '2000-01-01 00:00:00', '', '2000-01-01 00:00:00')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE hello_asso_token
        SQL);
    }
}
