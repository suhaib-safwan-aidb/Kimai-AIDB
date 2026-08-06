<?php

declare(strict_types=1);

namespace KimaiPlugin\KimaiJiraSyncBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Text field for the "jira_project_key" project meta field, wired into Kimai's
 * generic autocomplete widget (KimaiAutocomplete.js) so the value can be searched
 * against the real Jira instance instead of being typed blind.
 *
 * The field stays a plain editable text input: search results only add
 * suggestions on top, they don't restrict what can be typed/saved.
 */
final class JiraProjectKeySearchType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('autocomplete_url');
        $resolver->setAllowedTypes('autocomplete_url', ['null', 'string']);
        $resolver->setDefault('autocomplete_url', null);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // no known Kimai project yet (e.g. still creating one) means there is nothing to search against
        if (empty($options['autocomplete_url'])) {
            return;
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-form-widget' => 'autocomplete',
            'data-autocomplete-url' => $options['autocomplete_url'],
            'data-minimum-character' => 2,
            'data-create' => '1',
            'autocomplete' => 'off',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
