<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150608002226 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE refreshtoken DROP CONSTRAINT FK_7142379EA76ED395');
        $this->addSql('ALTER TABLE refreshtoken ADD CONSTRAINT FK_7142379EA76ED395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE accesstoken DROP CONSTRAINT FK_B39617F5A76ED395');
        $this->addSql('ALTER TABLE accesstoken ADD CONSTRAINT FK_B39617F5A76ED395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE RefreshToken DROP CONSTRAINT fk_7142379ea76ed395');
        $this->addSql('ALTER TABLE RefreshToken ADD CONSTRAINT fk_7142379ea76ed395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AccessToken DROP CONSTRAINT fk_b39617f5a76ed395');
        $this->addSql('ALTER TABLE AccessToken ADD CONSTRAINT fk_b39617f5a76ed395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
