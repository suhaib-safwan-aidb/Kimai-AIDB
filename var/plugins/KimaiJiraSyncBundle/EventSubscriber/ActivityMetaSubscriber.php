<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Entity\ActivityMeta;
use App\Event\ActivityMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ActivityMetaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ActivityMetaDefinitionEvent::class => ['onMetaDefinition', 200],
        ];
    }

    public function onMetaDefinition(ActivityMetaDefinitionEvent $event): void
    {
        $field = new ActivityMeta();
        $field->setName('jira_issue_key')
              ->setLabel('jira_sync.activity.jira_issue_key')
              ->setType(TextType::class)
              ->setIsVisible(true)
              ->setIsRequired(false);

        $event->getEntity()->setMetaField($field);
    }
}
