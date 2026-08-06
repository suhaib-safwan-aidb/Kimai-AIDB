<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConfigureMainMenuEvent::class => ['onMenuConfigure', 100]];
    }

    public function onMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $parent = new MenuItemModel(
            'jira_sync',
            'jira_sync.menu.label',
            null,
            [],
            'fas fa-sync',
        );

        $parent->addChild(
            new MenuItemModel(
                'jira_sync_my_credentials',
                'jira_sync.my_credentials.menu',
                'jira_sync_my_credentials',
                [],
                'fas fa-key',
            )
        );

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $parent->addChild(
                new MenuItemModel(
                    'jira_sync_license',
                    'jira_sync.license.menu',
                    'jira_sync_license',
                    [],
                    'fas fa-certificate',
                )
            );
        }

        $event->getMenu()->addChild($parent);
    }
}
