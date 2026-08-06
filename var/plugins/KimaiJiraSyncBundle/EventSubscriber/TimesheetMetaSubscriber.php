<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Entity\TimesheetMeta;
use App\Event\TimesheetMetaDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class TimesheetMetaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDefinitionEvent::class => ['onMetaDefinition', 200],
        ];
    }

    public function onMetaDefinition(TimesheetMetaDefinitionEvent $event): void
    {
        $entity = $event->getEntity();
        $entity->setMetaField($this->buildField('jira_worklog_id'));
        $entity->setMetaField($this->buildField('sync_status'));
        $entity->setMetaField($this->buildField('synced_at'));
    }

    private function buildField(string $name): TimesheetMeta
    {
        $field = new TimesheetMeta();
        $field->setName($name)
              ->setType(HiddenType::class)
              ->setIsVisible(false)
              ->setIsRequired(false);

        return $field;
    }
}
