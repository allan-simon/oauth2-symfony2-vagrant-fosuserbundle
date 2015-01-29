<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150128105430 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX uniq_93804ff8f85e0677');
        $this->addSql('DROP INDEX uniq_93804ff8e7927c74');
        $this->addSql('ALTER TABLE oauth_users ADD username_canonical VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD email_canonical VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD locked BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD expired BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD confirmation_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD password_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD roles TEXT NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD credentials_expired BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE oauth_users ADD credentials_expire_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE oauth_users ALTER username TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE oauth_users ALTER email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE oauth_users ALTER salt TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE oauth_users ALTER password TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE oauth_users RENAME COLUMN is_active TO enabled');
        $this->addSql('COMMENT ON COLUMN oauth_users.roles IS \'(DC2Type:array)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93804FF892FC23A8 ON oauth_users (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93804FF8A0D96FBF ON oauth_users (email_canonical)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_93804FF892FC23A8');
        $this->addSql('DROP INDEX UNIQ_93804FF8A0D96FBF');
        $this->addSql('ALTER TABLE oauth_users ADD is_active BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE oauth_users DROP username_canonical');
        $this->addSql('ALTER TABLE oauth_users DROP email_canonical');
        $this->addSql('ALTER TABLE oauth_users DROP enabled');
        $this->addSql('ALTER TABLE oauth_users DROP last_login');
        $this->addSql('ALTER TABLE oauth_users DROP locked');
        $this->addSql('ALTER TABLE oauth_users DROP expired');
        $this->addSql('ALTER TABLE oauth_users DROP expires_at');
        $this->addSql('ALTER TABLE oauth_users DROP confirmation_token');
        $this->addSql('ALTER TABLE oauth_users DROP password_requested_at');
        $this->addSql('ALTER TABLE oauth_users DROP roles');
        $this->addSql('ALTER TABLE oauth_users DROP credentials_expired');
        $this->addSql('ALTER TABLE oauth_users DROP credentials_expire_at');
        $this->addSql('ALTER TABLE oauth_users ALTER username TYPE VARCHAR(25)');
        $this->addSql('ALTER TABLE oauth_users ALTER email TYPE VARCHAR(25)');
        $this->addSql('ALTER TABLE oauth_users ALTER salt TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE oauth_users ALTER password TYPE VARCHAR(40)');
        $this->addSql('CREATE UNIQUE INDEX uniq_93804ff8f85e0677 ON oauth_users (username)');
        $this->addSql('CREATE UNIQUE INDEX uniq_93804ff8e7927c74 ON oauth_users (email)');
    }
}
