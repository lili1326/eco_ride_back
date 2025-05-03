<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503045205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE participe (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, covoiturage_id INT NOT NULL, INDEX IDX_9FFA8D4FB88E14F (utilisateur_id), INDEX IDX_9FFA8D462671590 (covoiturage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D462671590 FOREIGN KEY (covoiturage_id) REFERENCES ride (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE participe DROP FOREIGN KEY FK_9FFA8D4FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participe DROP FOREIGN KEY FK_9FFA8D462671590
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE participe
        SQL);
    }
}
