<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924115233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe_controle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, is_actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe_controle_abonnement (groupe_controle_id INT NOT NULL, abonnement_id INT NOT NULL, INDEX IDX_78BF5FB5FA545568 (groupe_controle_id), INDEX IDX_78BF5FB5F1D74413 (abonnement_id), PRIMARY KEY(groupe_controle_id, abonnement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe_controle_carte (groupe_controle_id INT NOT NULL, carte_id INT NOT NULL, INDEX IDX_4EDF0204FA545568 (groupe_controle_id), INDEX IDX_4EDF0204C9C7CEB6 (carte_id), PRIMARY KEY(groupe_controle_id, carte_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_abonnement ADD CONSTRAINT FK_78BF5FB5FA545568 FOREIGN KEY (groupe_controle_id) REFERENCES groupe_controle (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_abonnement ADD CONSTRAINT FK_78BF5FB5F1D74413 FOREIGN KEY (abonnement_id) REFERENCES abonnement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_carte ADD CONSTRAINT FK_4EDF0204FA545568 FOREIGN KEY (groupe_controle_id) REFERENCES groupe_controle (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_carte ADD CONSTRAINT FK_4EDF0204C9C7CEB6 FOREIGN KEY (carte_id) REFERENCES carte (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_abonnement DROP FOREIGN KEY FK_78BF5FB5FA545568
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_abonnement DROP FOREIGN KEY FK_78BF5FB5F1D74413
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_carte DROP FOREIGN KEY FK_4EDF0204FA545568
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_controle_carte DROP FOREIGN KEY FK_4EDF0204C9C7CEB6
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe_controle
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe_controle_abonnement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe_controle_carte
        SQL);
    }
}
