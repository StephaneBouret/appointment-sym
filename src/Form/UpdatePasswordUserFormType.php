<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UpdatePasswordUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'toggle' => true,
                'hidden_label' => 'Masquer',
                'visible_label' => 'Afficher',
                'label' => 'Mot de passe actuel',
                'label_attr' => [
                    'class' => 'lh-label fw-medium form-label'
                ],
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Mot de passe actuel',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new UserPassword([
                        'message' => 'Mauvaise valeur pour votre mot de passe actuel',
                    ])
                ]
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'Le mot de passe et la confirmation doivent être identiques',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Merci de saisir votre mot de passe'
                ],
                'constraints' => [
                    new PasswordStrength(
                        minScore: PasswordStrength::STRENGTH_STRONG,
                        message: 'Le mot de passe est trop faible. Veuillez utiliser un mot de passe plus fort.'
                    )
                ],
                'first_options' => [
                    'toggle' => true,
                    'hidden_label' => 'Masquer',
                    'visible_label' => 'Afficher',
                    'label' => 'Nouveau mot de passe',
                    'label_attr' => [
                        'class' => 'lh-label fw-medium form-label'
                    ],
                    'attr' => [
                        'placeholder' => 'Entrez votre nouveau mot de passe',
                        'class' => 'form-control'
                    ]
                ],
                'second_options' => [
                    'toggle' => true,
                    'hidden_label' => 'Masquer',
                    'visible_label' => 'Afficher',
                    'label' => 'Confirmer le nouveau mot de passe',
                    'label_attr' => [
                        'class' => 'lh-label fw-medium form-label'
                    ],
                    'attr' => [
                        'placeholder' => 'Confirmer le nouveau mot de passe',
                        'class' => 'form-control'
                    ]
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
