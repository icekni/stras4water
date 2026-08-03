<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250706173113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE saison (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date_debut DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', date_fin DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement ADD saison_id INT NOT NULL, DROP valid_from, DROP valid_until
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBF965414C FOREIGN KEY (saison_id) REFERENCES saison (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_351268BBF965414C ON abonnement (saison_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte DROP valid_from, DROP valid_until
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBF965414C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE saison
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte ADD valid_from DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD valid_until DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_351268BBF965414C ON abonnement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement ADD valid_from DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD valid_until DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', DROP saison_id
        SQL);
    }
}
