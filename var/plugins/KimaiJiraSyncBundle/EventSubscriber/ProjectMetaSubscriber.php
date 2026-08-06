<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\EventSubscriber;

use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\Type\YesNoType;
use KimaiPlugin\KimaiJiraSyncBundle\Form\JiraProjectKeySearchType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProjectMetaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
    ) {
    }

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
        $entity->setMetaField($this->buildProjectKeyField($entity->getId()));
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

    /**
     * The project key field is searchable (KimaiAutocomplete.js) once the project
     * already exists, since the search endpoint needs a Kimai project ID to look
     * up the configured Jira instance and the current user's credentials for it.
     */
    private function buildProjectKeyField(?int $projectId): ProjectMeta
    {
        $field = new ProjectMeta();
        $field->setName('jira_project_key')
              ->setLabel('jira_sync.project.jira_project_key')
              ->setType(JiraProjectKeySearchType::class)
              ->setIsVisible(true)
              ->setIsRequired(false);

        if ($projectId !== null) {
            $field->setOptions([
                'autocomplete_url' => $this->router->generate('jira_sync_project_key_search', ['id' => $projectId]),
            ]);
        }

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
