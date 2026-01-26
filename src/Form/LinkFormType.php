<?php

namespace App\Form;

use App\Entity\Link;
use Symfony\Component\Form\AbstractType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LinkFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'URL du réseau social :'
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Facebook' => Link::TYPE_FACEBOOK,
                    'Instagram' => Link::TYPE_INSTAGRAM,
                    'Linkedin' => Link::TYPE_LINKEDIN,
                    'Web' => Link::TYPE_WEB,
                    'X' => Link::TYPE_X,
                    'Youtube' => Link::TYPE_YOUTUBE,
                ],
                'label' => 'Type de réseau social'
            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'label' => 'Fichier svg',
                'delete_label' => 'Supprimer le svg',
                'download_uri' => false,
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('imageName', null, [
                'label' => 'Nom du fichier SVG :',
                'disabled' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
        ]);
    }
}
