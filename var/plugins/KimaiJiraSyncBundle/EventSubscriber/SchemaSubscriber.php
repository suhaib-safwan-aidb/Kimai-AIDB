<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\JiraCredential;
use KimaiPlugin\KimaiJiraSyncBundle\Entity\LicenseActivation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatically creates the plugin's database tables on first request if they don't exist yet.
 * This removes the need to run any install or migration commands after deploying the plugin.
 */
final class SchemaSubscriber implements EventSubscriberInterface
{
    private const MANAGED_TABLES = ['kimai2_jira_credentials', 'kimai2_jira_sync_license'];

    private bool $executed = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private ?SchemaTool $schemaTool = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1024]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->executed || !$event->isMainRequest()) {
            return;
        }

        $this->executed = true;

        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        if ($schemaManager->tablesExist(self::MANAGED_TABLES)) {
            return;
        }

        $metadata = [
            $this->entityManager->getClassMetadata(JiraCredential::class),
            $this->entityManager->getClassMetadata(LicenseActivation::class),
        ];

        $schemaTool = $this->schemaTool ?? new SchemaTool($this->entityManager);
        $schemaTool->updateSchema($metadata, saveMode: true);
    }
}
