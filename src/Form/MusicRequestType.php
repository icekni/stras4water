<?php

namespace App\Form;

use App\Entity\MusicRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MusicRequestType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du morceau',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex : Bailando',
                ],
            ])
            ->add('artiste', TextType::class, [
                'label' => 'Artiste',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex : Enrique Iglesias',
                ],
            ])
            ->add('youtubeUrl', UrlType::class, [
                'label' => 'Lien YouTube',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/...',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Proposer ce morceau',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MusicRequest::class,
            'csrf_protection' => true,
        ]);
    }
}