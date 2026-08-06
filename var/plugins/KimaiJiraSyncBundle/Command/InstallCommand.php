<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kimai:bundle:kimaijirasync:install',
    description: 'Install KimaiJiraSyncBundle – creates the required database table',
)]
final class InstallCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('KimaiJiraSync – Installation');

        $platform = $this->connection->getDatabasePlatform();
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['kimai2_jira_credentials'])) {
            $io->success('Table kimai2_jira_credentials already exists. Nothing to do.');
        } else {
            $this->connection->executeStatement('
                CREATE TABLE kimai2_jira_credentials (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    project_id INT NOT NULL,
                    jira_username VARCHAR(255) NOT NULL,
                    jira_api_token LONGTEXT NOT NULL,
                    UNIQUE INDEX jira_cred_user_project (user_id, project_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            ');

            $io->success('Table kimai2_jira_credentials created successfully.');
        }

        if ($schemaManager->tablesExist(['kimai2_jira_sync_license'])) {
            $io->success('Table kimai2_jira_sync_license already exists. Nothing to do.');
        } else {
            $this->connection->executeStatement('
                CREATE TABLE kimai2_jira_sync_license (
                    id INT AUTO_INCREMENT NOT NULL,
                    license_key VARCHAR(255) NOT NULL,
                    instance_id VARCHAR(500) NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    expires_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    activated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    last_verified_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            ');

            $io->success('Table kimai2_jira_sync_license created successfully.');
        }

        return Command::SUCCESS;
    }
}
