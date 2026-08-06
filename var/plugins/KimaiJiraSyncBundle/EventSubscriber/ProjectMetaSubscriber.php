<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\Type\YesNoType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ProjectMetaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProjectMetaDefinitionEvent::class => ['onMetaDefinition', 200],
        ];
    }

    public function onMetaDefinition(ProjectMetaDefinitionEvent $event): void
    {
        $entity = $event->getEntity();
        $entity->setMetaField($this->buildTextField('jira_instance_url', 'jira_sync.project.jira_instance_url'));
        $entity->setMetaField($this->buildTextField('jira_project_key', 'jira_sync.project.jira_project_key'));
        $entity->setMetaField($this->buildNumberField('time_multiplier', 'jira_sync.project.time_multiplier'));
        $entity->setMetaField($this->buildCheckboxField('sync_tasks_enabled', 'jira_sync.project.sync_tasks_enabled'));
    }

    private function buildTextField(string $name, string $label): ProjectMeta
    {
        $field = new ProjectMeta();
        $field->setName($name)
              ->setLabel($label)
              ->setType(TextType::class)
              ->setIsVisible(true)
              ->setIsRequired(false);

        return $field;
    }

    private function buildNumberField(string $name, string $label): ProjectMeta
    {
        $field = new ProjectMeta();
        $field->setName($name)
              ->setLabel($label)
              ->setType(NumberType::class)
              ->setIsVisible(true)
              ->setIsRequired(false)
              ->setValue('1.0');

        return $field;
    }

    private function buildCheckboxField(string $name, string $label): ProjectMeta
    {
        $field = new ProjectMeta();
        $field->setName($name)
              ->setLabel($label)
              ->setType(YesNoType::class)
              ->setIsVisible(true)
              ->setIsRequired(false);

        return $field;
    }
}
