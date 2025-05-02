<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250502134430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD auteur_id INT NOT NULL, ADD conducteur_id INT DEFAULT NULL, ADD covoiturage_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C660BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C6F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C662671590 FOREIGN KEY (covoiturage_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_794381C660BB6FE6 ON review (auteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_794381C6F16F4AC6 ON review (conducteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_794381C662671590 ON review (covoiturage_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C660BB6FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C6F16F4AC6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C662671590
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_794381C660BB6FE6 ON review
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_794381C6F16F4AC6 ON review
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_794381C662671590 ON review
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP auteur_id, DROP conducteur_id, DROP covoiturage_id
        SQL);
    }
}
