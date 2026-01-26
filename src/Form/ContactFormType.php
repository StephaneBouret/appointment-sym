<?php

namespace App\Form;

use App\Entity\Contact;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Prénom',
                    'class' => 'input100'
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom',
                    'class' => 'input100'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'e.g. picard@starfleet.org',
                    'class' => 'input100'
                ],
            ])
            ->add('phone', PhoneNumberType::class, [
                'default_region' => 'FR',
                'format' => PhoneNumberFormat::NATIONAL,
                'label' => false,
                'attr' => [
                    'placeholder' => '01 02 03 04 05',
                    'class' => 'input100'
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => false,
                'required' => true,
                'row_attr' => ['rows' => '5'],
                'attr' => [
                    'placeholder' => 'Votre demande',
                    'class' => 'input100'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
