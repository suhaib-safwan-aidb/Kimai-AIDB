<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates kimai2_jira_credentials table for KimaiJiraSyncBundle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE kimai2_jira_credentials (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                project_id INT NOT NULL,
                jira_username VARCHAR(255) NOT NULL,
                jira_api_token LONGTEXT NOT NULL,
                UNIQUE INDEX jira_cred_user_project (user_id, project_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE kimai2_jira_credentials');
    }
}
