<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250626151756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE abonnement (id INT AUTO_INCREMENT NOT NULL, discipline_id INT NOT NULL, nom VARCHAR(255) NOT NULL, valid_from DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', valid_until DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', tarif DOUBLE PRECISION NOT NULL, tarif_reduit DOUBLE PRECISION DEFAULT NULL, has_tarif_reduit TINYINT(1) NOT NULL, is_actif TINYINT(1) NOT NULL, INDEX IDX_351268BBA5522701 (discipline_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE abonnement_souscrit (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, abonnement_id INT NOT NULL, statut VARCHAR(255) NOT NULL, INDEX IDX_12649B4A76ED395 (user_id), INDEX IDX_12649B4F1D74413 (abonnement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE carte (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, nombre_seances INT NOT NULL, valid_from DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', valid_until DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', tarif DOUBLE PRECISION NOT NULL, tarif_reduit DOUBLE PRECISION DEFAULT NULL, has_tarif_reduit TINYINT(1) NOT NULL, is_actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE carte_discipline (carte_id INT NOT NULL, discipline_id INT NOT NULL, INDEX IDX_BB383962C9C7CEB6 (carte_id), INDEX IDX_BB383962A5522701 (discipline_id), PRIMARY KEY(carte_id, discipline_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE carte_souscrite (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, carte_id INT NOT NULL, seances_restantes INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', statut VARCHAR(255) NOT NULL, INDEX IDX_DC10A73DA76ED395 (user_id), INDEX IDX_DC10A73DC9C7CEB6 (carte_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE discipline (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, is_actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, montant DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(10) NOT NULL, checkout_id VARCHAR(255) DEFAULT NULL, url_recu_fiscal VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, wants_recu_fiscal TINYINT(1) NOT NULL, token VARCHAR(255) DEFAULT NULL, moyen_paiement VARCHAR(255) NOT NULL, type_don VARCHAR(255) NOT NULL, numero_ordre_rf VARCHAR(255) DEFAULT NULL, INDEX IDX_31E581A0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE periode_essai (id INT AUTO_INCREMENT NOT NULL, debut DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', fin DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE seance_essai (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, discipline_id INT NOT NULL, periode_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_EF53460DA76ED395 (user_id), INDEX IDX_EF53460DA5522701 (discipline_id), INDEX IDX_EF53460DF384C1CF (periode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, is_adherent TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBA5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit ADD CONSTRAINT FK_12649B4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit ADD CONSTRAINT FK_12649B4F1D74413 FOREIGN KEY (abonnement_id) REFERENCES abonnement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_discipline ADD CONSTRAINT FK_BB383962C9C7CEB6 FOREIGN KEY (carte_id) REFERENCES carte (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_discipline ADD CONSTRAINT FK_BB383962A5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite ADD CONSTRAINT FK_DC10A73DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite ADD CONSTRAINT FK_DC10A73DC9C7CEB6 FOREIGN KEY (carte_id) REFERENCES carte (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE donation ADD CONSTRAINT FK_31E581A0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai ADD CONSTRAINT FK_EF53460DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai ADD CONSTRAINT FK_EF53460DA5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai ADD CONSTRAINT FK_EF53460DF384C1CF FOREIGN KEY (periode_id) REFERENCES periode_essai (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBA5522701
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit DROP FOREIGN KEY FK_12649B4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit DROP FOREIGN KEY FK_12649B4F1D74413
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_discipline DROP FOREIGN KEY FK_BB383962C9C7CEB6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_discipline DROP FOREIGN KEY FK_BB383962A5522701
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite DROP FOREIGN KEY FK_DC10A73DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite DROP FOREIGN KEY FK_DC10A73DC9C7CEB6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE donation DROP FOREIGN KEY FK_31E581A0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai DROP FOREIGN KEY FK_EF53460DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai DROP FOREIGN KEY FK_EF53460DA5522701
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE seance_essai DROP FOREIGN KEY FK_EF53460DF384C1CF
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE abonnement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE abonnement_souscrit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte_discipline
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte_souscrite
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discipline
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE donation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE periode_essai
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE seance_essai
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
