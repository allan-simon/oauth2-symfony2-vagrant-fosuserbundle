<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150116135531 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE RefreshToken_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE oauth_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Client_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE AuthCode_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE AccessToken_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE RefreshToken (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7142379E5F37A13B ON RefreshToken (token)');
        $this->addSql('CREATE INDEX IDX_7142379E19EB6921 ON RefreshToken (client_id)');
        $this->addSql('CREATE INDEX IDX_7142379EA76ED395 ON RefreshToken (user_id)');
        $this->addSql('CREATE TABLE oauth_users (id INT NOT NULL, username VARCHAR(25) NOT NULL, email VARCHAR(25) NOT NULL, salt VARCHAR(32) NOT NULL, password VARCHAR(40) NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93804FF8F85E0677 ON oauth_users (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93804FF8E7927C74 ON oauth_users (email)');
        $this->addSql('CREATE TABLE Client (id INT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris TEXT NOT NULL, secret VARCHAR(255) NOT NULL, allowed_grant_types TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN Client.redirect_uris IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN Client.allowed_grant_types IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE AuthCode (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri TEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1D7D1775F37A13B ON AuthCode (token)');
        $this->addSql('CREATE INDEX IDX_F1D7D17719EB6921 ON AuthCode (client_id)');
        $this->addSql('CREATE INDEX IDX_F1D7D177A76ED395 ON AuthCode (user_id)');
        $this->addSql('CREATE TABLE AccessToken (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B39617F55F37A13B ON AccessToken (token)');
        $this->addSql('CREATE INDEX IDX_B39617F519EB6921 ON AccessToken (client_id)');
        $this->addSql('CREATE INDEX IDX_B39617F5A76ED395 ON AccessToken (user_id)');
        $this->addSql('ALTER TABLE RefreshToken ADD CONSTRAINT FK_7142379E19EB6921 FOREIGN KEY (client_id) REFERENCES Client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE RefreshToken ADD CONSTRAINT FK_7142379EA76ED395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AuthCode ADD CONSTRAINT FK_F1D7D17719EB6921 FOREIGN KEY (client_id) REFERENCES Client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AuthCode ADD CONSTRAINT FK_F1D7D177A76ED395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AccessToken ADD CONSTRAINT FK_B39617F519EB6921 FOREIGN KEY (client_id) REFERENCES Client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE AccessToken ADD CONSTRAINT FK_B39617F5A76ED395 FOREIGN KEY (user_id) REFERENCES oauth_users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE RefreshToken DROP CONSTRAINT FK_7142379EA76ED395');
        $this->addSql('ALTER TABLE AuthCode DROP CONSTRAINT FK_F1D7D177A76ED395');
        $this->addSql('ALTER TABLE AccessToken DROP CONSTRAINT FK_B39617F5A76ED395');
        $this->addSql('ALTER TABLE RefreshToken DROP CONSTRAINT FK_7142379E19EB6921');
        $this->addSql('ALTER TABLE AuthCode DROP CONSTRAINT FK_F1D7D17719EB6921');
        $this->addSql('ALTER TABLE AccessToken DROP CONSTRAINT FK_B39617F519EB6921');
        $this->addSql('DROP SEQUENCE RefreshToken_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE oauth_users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Client_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE AuthCode_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE AccessToken_id_seq CASCADE');
        $this->addSql('DROP TABLE RefreshToken');
        $this->addSql('DROP TABLE oauth_users');
        $this->addSql('DROP TABLE Client');
        $this->addSql('DROP TABLE AuthCode');
        $this->addSql('DROP TABLE AccessToken');
    }
}
