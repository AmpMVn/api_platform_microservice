<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220527083809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('TRUNCATE price_deal CASCADE');
        $this->addSql('delete from sync_rentsoft where remote_action = \'settings-pricedeals\'');
        $this->addSql('DELETE FROM price_deal CASCADE');
        $this->addSql('ALTER TABLE price_deal DROP CONSTRAINT fk_9eac33ac19eb6921');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP INDEX idx_9eac33ac19eb6921');
        $this->addSql('ALTER TABLE price_deal ALTER client_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, branch VARCHAR(255) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN client.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE price_deal ALTER client_id DROP NOT NULL');
        $this->addSql('ALTER TABLE price_deal ADD CONSTRAINT fk_9eac33ac19eb6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_9eac33ac19eb6921 ON price_deal (client_id)');
    }
}
