<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230129154716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ALTER COLUMN id TYPE TEXT, ALTER COLUMN id SET DEFAULT uuid_generate_v4()');
        $this->addSql('UPDATE article SET id = uuid_generate_v4() WHERE id ~ \'^[0-9]*$\'');
        $this->addSql('ALTER TABLE article ALTER COLUMN title_image DROP NOT NULL');
        $this->addSql('ALTER TABLE article ADD link TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD created_by TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE article SET created_at = now() WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE article ALTER COLUMN created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP id, ADD id SERIAL NOT NULL PRIMARY KEY');
        $this->addSql('ALTER TABLE article ALTER COLUMN title_image SET NOT NULL');
        $this->addSql('ALTER TABLE article DROP link');
        $this->addSql('ALTER TABLE article DROP created_by');
        $this->addSql('ALTER TABLE article DROP created_at');
    }
}
