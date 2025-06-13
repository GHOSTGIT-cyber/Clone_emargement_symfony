<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611220110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE session_groupe (session_id INT NOT NULL, groupe_id INT NOT NULL, INDEX IDX_6BD28678613FECDF (session_id), INDEX IDX_6BD286787A45358C (groupe_id), PRIMARY KEY(session_id, groupe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session_groupe ADD CONSTRAINT FK_6BD28678613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session_groupe ADD CONSTRAINT FK_6BD286787A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP FOREIGN KEY FK_D044D5D47A45358C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D044D5D47A45358C ON session
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP groupe_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE session_groupe DROP FOREIGN KEY FK_6BD28678613FECDF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session_groupe DROP FOREIGN KEY FK_6BD286787A45358C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE session_groupe
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD groupe_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD CONSTRAINT FK_D044D5D47A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D044D5D47A45358C ON session (groupe_id)
        SQL);
    }
}
