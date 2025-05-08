<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508094015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD voiture_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0181A8BA FOREIGN KEY (voiture_id) REFERENCES car (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3D7CD0181A8BA ON ride (voiture_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0181A8BA
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B3D7CD0181A8BA ON ride
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP voiture_id
        SQL);
    }
}
