<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [PasswordType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'toggle' => false,
            'visible_label' => 'Afficher',
            'hidden_label' => 'Masquer',
        ]);
        $resolver->setAllowedTypes('toggle', 'bool');
        $resolver->setAllowedTypes('visible_label', 'string');
        $resolver->setAllowedTypes('hidden_label', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['toggle']) {
            return;
        }

        array_splice($view->vars['block_prefixes'], -1, 0, ['toggle_password']);
        $view->vars['visible_label'] = $options['visible_label'];
        $view->vars['hidden_label'] = $options['hidden_label'];
        $view->vars['attr']['data-toggle-password-target'] = 'input';
    }
}
