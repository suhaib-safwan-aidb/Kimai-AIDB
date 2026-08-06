<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates kimai2_jira_sync_license table for license activation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE kimai2_jira_sync_license (
                id INT AUTO_INCREMENT NOT NULL,
                license_key VARCHAR(255) NOT NULL,
                instance_id VARCHAR(500) NOT NULL,
                status VARCHAR(50) NOT NULL,
                expires_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\',
                activated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                last_verified_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE kimai2_jira_sync_license');
    }
}
