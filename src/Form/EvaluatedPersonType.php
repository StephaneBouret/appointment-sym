<?php

namespace App\Form;

use App\Entity\EvaluatedPerson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluatedPersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom de la personne évaluée',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tous les prénoms enregistrés à l\'état civil, séparés par des espaces',
                    'data-names-formatter-target' => 'input',
                ],
                'help' => 'Tous les prénoms enregistrés à l\'état civil',
                'help_attr' => [
                    'class' => 'text-muted small fst-italic mt-1 d-block'
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom de la personne évaluée',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Tous les noms enregistrés à l\'état civil, séparés par des espaces',
                    'data-names-formatter-target' => 'input',
                ],
                'help' => 'Tous les noms enregistrés à l\'état civil',
                'help_attr' => [
                    'class' => 'text-muted small fst-italic mt-1 d-block'
                ],
            ])
            ->add('patronyms', TextType::class, [
                'label' => 'Identité sociale',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Le prénom et le nom que vous utilisez tous les jours',
                    'data-names-formatter-target' => 'input',
                ],
                'help' => 'Par « Identité sociale », on entend le prénom et le nom que vous utilisez tous les jours',
                'help_attr' => [
                    'class' => 'text-muted small fst-italic mt-1 d-block'
                ],
            ])
            ->add('birthdate', DateType::class, [
                'required' => true,
                'widget' => 'single_text',
                'label' => 'Date de naissance',
                // 'input' => 'datetime',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => EvaluatedPerson::class,
        ]);
    }
}
