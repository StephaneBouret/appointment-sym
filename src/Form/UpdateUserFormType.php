<?php

namespace App\Form;

use App\Entity\User;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class UpdateUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Votre email :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'required' => true,
                'attr' => [
                    'placeholder' => 'Merci de saisir votre adresse email'
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Votre prénom :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre prénom'
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Votre nom :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre nom'
                ],
            ])
            ->add('adress', TextType::class, [
                'label' => 'Votre adresse :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre adresse',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Votre code postal :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre code postal'
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Votre ville :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre ville'
                ],
            ])
            ->add('phone', PhoneNumberType::class, [
                'default_region' => 'FR',
                'format' => PhoneNumberFormat::NATIONAL,
                'label' => 'Votre téléphone :',
                'label_attr' => [
                    'class' => 'lh-label fw-medium'
                ],
                'attr' => [
                    'placeholder' => 'Merci de saisir votre téléphone'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
