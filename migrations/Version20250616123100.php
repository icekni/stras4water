<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616123100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE donation ADD user_id INT DEFAULT NULL, ADD wants_recu_fiscal TINYINT(1) NOT NULL, DROP nom, DROP prenom, DROP adresse_numero, DROP adresse_rue, DROP adresse_code_postal, DROP adresse_ville, DROP type_don, DROP moyen_paiement, DROP adresse_pays, DROP date_de_naissance, CHANGE date created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE donation ADD CONSTRAINT FK_31E581A0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_31E581A0A76ED395 ON donation (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_31E581A0A76ED395 ON donation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE donation ADD nom VARCHAR(255) DEFAULT NULL, ADD prenom VARCHAR(255) DEFAULT NULL, ADD adresse_numero VARCHAR(255) DEFAULT NULL, ADD adresse_rue VARCHAR(255) DEFAULT NULL, ADD adresse_code_postal VARCHAR(255) DEFAULT NULL, ADD adresse_ville VARCHAR(255) DEFAULT NULL, ADD type_don VARCHAR(20) NOT NULL, ADD moyen_paiement VARCHAR(20) NOT NULL, ADD adresse_pays VARCHAR(255) DEFAULT NULL, ADD date_de_naissance DATE NOT NULL COMMENT '(DC2Type:date_immutable)', DROP user_id, DROP wants_recu_fiscal, CHANGE created_at date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }
}
