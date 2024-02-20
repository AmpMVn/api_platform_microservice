<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220421093658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP CONSTRAINT fk_23a0e6619eb6921');
        $this->addSql('DROP INDEX idx_23a0e6619eb6921');
        $this->addSql('ALTER TABLE article ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE price_discount DROP CONSTRAINT fk_83568f1919eb6921');
        $this->addSql('DROP INDEX idx_83568f1919eb6921');
        $this->addSql('ALTER TABLE price_discount ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE price_rate_group DROP CONSTRAINT fk_490852e219eb6921');
        $this->addSql('DROP INDEX idx_490852e219eb6921');
        $this->addSql('ALTER TABLE price_rate_group ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE settings_attribute_set_group DROP CONSTRAINT fk_92ca1e5919eb6921');
        $this->addSql('DROP INDEX idx_92ca1e5919eb6921');
        $this->addSql('ALTER TABLE settings_attribute_set_group ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE settings_category DROP CONSTRAINT fk_202b2dc519eb6921');
        $this->addSql('DROP INDEX idx_202b2dc519eb6921');
        $this->addSql('ALTER TABLE settings_category ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE settings_location DROP CONSTRAINT fk_78f9bdcf19eb6921');
        $this->addSql('DROP INDEX idx_78f9bdcf19eb6921');
        $this->addSql('ALTER TABLE settings_location ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE settings_storage DROP CONSTRAINT fk_ea7eb30419eb6921');
        $this->addSql('DROP INDEX idx_ea7eb30419eb6921');
        $this->addSql('ALTER TABLE settings_storage ALTER client_id SET NOT NULL');
    }
}
