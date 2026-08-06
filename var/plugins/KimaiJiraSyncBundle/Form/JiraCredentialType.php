<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Form;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class JiraCredentialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userId', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'username',
                'label'        => 'jira_sync.credentials.user_id',
            ])
            ->add('projectId', EntityType::class, [
                'class'        => Project::class,
                'choice_label' => 'name',
                'label'        => 'jira_sync.credentials.project_id',
            ])
            ->add('jiraUsername', TextType::class, [
                'label' => 'jira_sync.credentials.jira_username',
            ])
            ->add('jiraApiToken', PasswordType::class, [
                'label'    => 'jira_sync.credentials.api_token',
                'required' => false,
                'attr'     => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
