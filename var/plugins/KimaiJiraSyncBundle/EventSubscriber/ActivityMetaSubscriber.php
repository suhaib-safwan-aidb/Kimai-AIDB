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
        $event->getEntity()->setMetaField($this->buildField('jira_issue_key', 'jira_sync.activity.jira_issue_key'));
        $event->getEntity()->setMetaField($this->buildField('jira_status', 'jira_sync.activity.jira_status'));
        $event->getEntity()->setMetaField($this->buildField('jira_assignee', 'jira_sync.activity.jira_assignee'));
    }

    private function buildField(string $name, string $label): ActivityMeta
    {
        $field = new ActivityMeta();
        $field->setName($name)
              ->setLabel($label)
              ->setType(TextType::class)
              ->setIsVisible(true)
              ->setIsRequired(false);

        return $field;
    }
}
