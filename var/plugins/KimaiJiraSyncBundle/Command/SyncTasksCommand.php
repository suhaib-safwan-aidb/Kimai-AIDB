<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Command;

use App\Repository\ProjectRepository;
use KimaiPlugin\KimaiJiraSyncBundle\Service\TaskImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kimai:bundle:kimaijirasync:sync-tasks',
    description: 'Import Jira tasks as Kimai activities for all configured projects',
)]
final class SyncTasksCommand extends Command
{
    public function __construct(
        private readonly TaskImportService $taskImportService,
        private readonly ProjectRepository $projectRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'user',
            'u',
            InputOption::VALUE_REQUIRED,
            'User ID whose Jira credentials to use for import',
            '1',
        );
        $this->addOption(
            'project',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Import only for this project ID (default: all enabled projects)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int) $input->getOption('user');
        $projectId = $input->getOption('project');

        if ($projectId !== null) {
            $projects = array_filter(
                [$this->projectRepository->find((int) $projectId)],
                static fn ($p) => $p !== null,
            );
        } else {
            $projects = $this->projectRepository->findAll();
        }

        $total = 0;
        foreach ($projects as $project) {
            $count = $this->taskImportService->importForProject($project, $userId);
            if ($count > 0) {
                $io->writeln(sprintf('Project "%s": imported/updated %d task(s).', $project->getName(), $count));
                $total += $count;
            }
        }

        $io->success(sprintf('Task sync completed. Total: %d task(s) imported/updated.', $total));
        return Command::SUCCESS;
    }
}
