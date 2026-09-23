<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l’événement',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('type', EntityType::class, [
                'class' => EventType::class,
                'choice_label' => 'name',
                'label' => 'Type',
                'placeholder' => 'Sélectionner un type',
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'Début',
                'widget' => 'single_text',
            ])
            ->add('endAt', DateTimeType::class, [
                'label' => 'Fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('image', TextType::class, [
                'label' => 'Image',
                'required' => false,
                'help' => 'Nom ou chemin de l’image. L’upload sera ajouté ensuite.',
            ])
            ->add('workshops', CollectionType::class, [
                'entry_type' => EventWorkshopFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ])
            ->add('links', CollectionType::class, [
                'entry_type' => EventLinkFormType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}