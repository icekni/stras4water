<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801190226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit ADD tarif_reduit_justifie TINYINT(1) NOT NULL, DROP justificatif
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite ADD tarif_reduit_verifie TINYINT(1) NOT NULL, DROP justificatif
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_souscrite ADD justificatif VARCHAR(255) DEFAULT NULL, DROP tarif_reduit_verifie
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement_souscrit ADD justificatif VARCHAR(255) DEFAULT NULL, DROP tarif_reduit_justifie
        SQL);
    }
}
