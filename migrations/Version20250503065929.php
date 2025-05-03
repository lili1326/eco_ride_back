<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503065929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE participe MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `primary` ON participe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participe ADD statut VARCHAR(20) NOT NULL, DROP id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participe ADD PRIMARY KEY (utilisateur_id, covoiturage_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE participe ADD id INT AUTO_INCREMENT NOT NULL, DROP statut, DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
    }
}
