<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectCredentialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_admin']) {
            $builder->add('userId', EntityType::class, [
                'class'        => User::class,
                'choice_label' => 'username',
                'label'        => 'jira_sync.credentials.user_id',
            ]);
        }

        $builder
            ->add('jiraUsername', TextType::class, [
                'label' => 'jira_sync.credentials.jira_username',
            ])
            ->add('jiraApiToken', PasswordType::class, [
                'label'    => 'jira_sync.credentials.api_token',
                'required' => !$options['is_edit'],
                'attr'     => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
            'is_edit'  => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
