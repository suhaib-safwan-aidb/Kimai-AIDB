<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Event\ProjectDetailControllerEvent;
use KimaiPlugin\KimaiJiraSyncBundle\Controller\ProjectCredentialController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProjectDetailSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProjectDetailControllerEvent::class => ['onProjectDetail', 10],
        ];
    }

    public function onProjectDetail(ProjectDetailControllerEvent $event): void
    {
        $event->addController(ProjectCredentialController::class . '::panelAction');
    }
}
